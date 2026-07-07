<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Donation;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DonorDonationController extends Controller
{
    use DonorPortalScoping;

    public function donations(Request $request, Organization $organization)
    {
        $donor = $request->donor;

        $request->validate([
            'status' => 'nullable|string',
            'type' => 'nullable|string',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0',
        ]);

        $subscriptionFilter = $request->query('subscription');
        $subscription = null;

        $query = $this->scopeToOrg($donor->donations(), $organization)->with('campaign.organization');

        if ($subscriptionFilter !== null) {
            $subscription = $this->scopeToOrg($donor->subscriptions(), $organization)->where('public_id', $subscriptionFilter)->first();
            if ($subscription !== null) {
                $query->where('subscription_id', $subscription->getKey());
            }
        }

        $status = $request->enum('status', DonationStatus::class);
        if ($status !== null) {
            $query->where('status', $status);
        }

        $type = $request->enum('type', DonationType::class);
        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        if ($request->filled('amount_min')) {
            $query->where('gross_amount', '>=', $request->query('amount_min'));
        }

        if ($request->filled('amount_max')) {
            $query->where('gross_amount', '<=', $request->query('amount_max'));
        }

        $totalGiven = $this->getTotalGiven($donor, $organization);
        $currencyBreakdown = $this->getCurrencyBreakdown($donor, $organization);

        return view('donor.donations', [
            'donor' => $donor,
            'organization' => $organization,
            'totalGiven' => $totalGiven,
            'currencyBreakdown' => $currencyBreakdown,
            'donationCount' => $this->scopeToOrg($donor->donations(), $organization)
                ->where('status', DonationStatus::Succeeded)
                ->count(),
            'donations' => $query->latest()->paginate(10)->withQueryString(),
            'subscription' => $subscription,
            'filters' => [
                'status' => $request->query('status', ''),
                'type' => $request->query('type', ''),
                'date_from' => $request->query('date_from', ''),
                'date_to' => $request->query('date_to', ''),
                'amount_min' => $request->query('amount_min', ''),
                'amount_max' => $request->query('amount_max', ''),
            ],
        ]);
    }

    public function detail(Organization $organization, Donation $donation)
    {
        $donor = request()->donor;

        $scopedDonation = $this->scopeToOrg($donor->donations(), $organization)
            ->where('donations.id', $donation->getKey())
            ->with(['campaign.organization', 'subscription'])
            ->firstOrFail();

        return view('donor.donations.detail', [
            'donation' => $scopedDonation,
            'organization' => $organization,
        ]);
    }

    public function downloadAllReceipts(Organization $organization)
    {
        $donor = request()->donor;

        $donations = $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->with(['campaign.organization', 'donor'])
            ->latest()
            ->get();

        if ($donations->isEmpty()) {
            return redirect()->route('donorportal.donations', $organization)
                ->with('error', 'No receipts available to download.');
        }

        $filename = config('app.name').'-'.$organization->code.'-all-receipts-'.now()->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('emails.donation-receipt-pdf-bulk', [
            'donations' => $donations,
        ]);

        return $pdf->download($filename);
    }
}
