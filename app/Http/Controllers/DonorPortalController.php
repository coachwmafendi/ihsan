<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donor;
use App\Models\Organization;
use App\Support\Currency;
use Barryvdh\DomPDF\Facade\Pdf;

class DonorPortalController extends Controller
{
    private function getDonor(): ?Donor
    {
        $donorId = session('donor_id');
        if ($donorId === null) {
            return null;
        }

        return Donor::query()->find($donorId);
    }

    private function getTotalGiven(Donor $donor): float
    {
        return (float) $donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->sum(\DB::raw('COALESCE(base_amount, gross_amount)'));
    }

    private function getCurrencyBreakdown(Donor $donor): array
    {
        return $donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw('currency, SUM(gross_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->mapWithKeys(function ($total, $currency) {
                return [$currency => Currency::symbol($currency).' '.number_format((float) $total, 2)];
            })
            ->toArray();
    }

    private function formatAmount(float $amount): string
    {
        return '≈ MYR '.number_format($amount, 2);
    }

    public function dashboard()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        $totalGiven = $this->getTotalGiven($donor);
        $currencyBreakdown = $this->getCurrencyBreakdown($donor);
        $hasMultipleCurrencies = count($currencyBreakdown) > 1;

        $activeSubscriptions = $donor->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $monthlyRecurring = $donor->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->sum('amount');

        $monthlyDonations = $donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw("strftime('%Y-%m', created_at) as month, SUM(COALESCE(base_amount, gross_amount)) as total")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $campaignBreakdown = $donor->donations()
            ->where('donations.status', DonationStatus::Succeeded)
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.title as campaign, donations.currency, SUM(donations.gross_amount) as total')
            ->groupBy('campaigns.title', 'donations.currency')
            ->orderByDesc('total')
            ->get()
            ->groupBy('campaign')
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    $symbol = match ($item->currency) {
                        'usd' => '$',
                        'sgd' => 'S$',
                        default => 'RM',
                    };

                    return [$item->currency => $symbol.' '.number_format((float) $item->total, 2)];
                });
            });

        $recentDonations = $donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign.organization')
            ->latest()
            ->limit(5)
            ->get();

        $primaryOrganization = $recentDonations->first()?->campaign?->organization;

        $activeSubscriptionsList = $donor->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->get();

        $monthlyRecurringByCurrency = $activeSubscriptionsList
            ->groupBy('currency')
            ->map(function ($subs, $currency) {
                $total = $subs->sum('amount');

                return Currency::symbol($currency).' '.number_format((float) $total, 2);
            })
            ->toArray();

        return view('donor.dashboard', [
            'donor' => $donor,
            'totalGiven' => $totalGiven,
            'currencyBreakdown' => $currencyBreakdown,
            'activeSubscriptions' => $activeSubscriptions,
            'monthlyRecurringFormatted' => $monthlyRecurringByCurrency,
            'monthlyDonations' => $monthlyDonations,
            'campaignBreakdown' => $campaignBreakdown,
            'recentDonations' => $recentDonations,
            'primaryOrganization' => $primaryOrganization,
        ]);
    }

    private function getPrimaryOrganization(Donor $donor): ?Organization
    {
        return $donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign.organization')
            ->latest()
            ->first()?->campaign?->organization;
    }

    public function donations()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        $subscriptionFilter = request()->query('subscription');
        $subscription = null;

        $query = $donor->donations()->with('campaign.organization');

        if ($subscriptionFilter !== null) {
            $subscription = $donor->subscriptions()->find($subscriptionFilter);
            if ($subscription !== null) {
                $query->where('subscription_id', $subscription->getKey());
            }
        }

        $totalGiven = $this->getTotalGiven($donor);
        $currencyBreakdown = $this->getCurrencyBreakdown($donor);

        return view('donor.donations', [
            'donor' => $donor,
            'totalGiven' => $totalGiven,
            'currencyBreakdown' => $currencyBreakdown,
            'donationCount' => $donor->donations()->where('status', DonationStatus::Succeeded)->count(),
            'donations' => $query->latest()->paginate(10),
            'subscription' => $subscription,
            'primaryOrganization' => $this->getPrimaryOrganization($donor),
        ]);
    }

    public function subscriptions()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        return view('donor.subscriptions', [
            'donor' => $donor,
            'subscriptions' => $donor->subscriptions()->with('campaign.organization')->latest()->paginate(10),
            'primaryOrganization' => $this->getPrimaryOrganization($donor),
        ]);
    }

    public function downloadAllReceipts()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        $donations = $donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->with(['campaign.organization', 'donor'])
            ->latest()
            ->get();

        if ($donations->isEmpty()) {
            return redirect()->route('donorportal.donations')->with('error', 'No receipts available to download.');
        }

        $filename = config('app.name').'-all-receipts-'.now()->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('emails.donation-receipt-pdf-bulk', [
            'donations' => $donations,
        ]);

        return $pdf->download($filename);
    }
}
