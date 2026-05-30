<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;

class DonorDonationController extends Controller
{
    use DonorPortalScoping;

    public function donations(Organization $organization)
    {
        $donor = request()->donor;

        $subscriptionFilter = request()->query('subscription');
        $subscription = null;

        $query = $this->scopeToOrg($donor->donations(), $organization)->with('campaign.organization');

        if ($subscriptionFilter !== null) {
            $subscription = $this->scopeToOrg($donor->subscriptions(), $organization)->find($subscriptionFilter);
            if ($subscription !== null) {
                $query->where('subscription_id', $subscription->getKey());
            }
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
            'donations' => $query->latest()->paginate(10),
            'subscription' => $subscription,
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
