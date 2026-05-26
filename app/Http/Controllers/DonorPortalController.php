<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donor;

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
                $symbol = match ($currency) {
                    'usd' => '$',
                    'sgd' => 'S$',
                    default => 'RM',
                };

                return [$currency => $symbol.' '.number_format((float) $total, 2)];
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
            ->selectRaw('campaigns.title as campaign, SUM(COALESCE(donations.base_amount, donations.gross_amount)) as total')
            ->groupBy('campaigns.title')
            ->orderByDesc('total')
            ->get();

        $recentDonations = $donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign.organization')
            ->latest()
            ->limit(5)
            ->get();

        $activeSubscriptionsList = $donor->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->get();

        $monthlyRecurringByCurrency = $activeSubscriptionsList
            ->groupBy('currency')
            ->map(function ($subs, $currency) {
                $symbol = match ($currency) {
                    'usd' => '$',
                    'sgd' => 'S$',
                    default => 'RM',
                };
                $total = $subs->sum('amount');

                return $symbol.' '.number_format((float) $total, 2);
            })
            ->toArray();

        return view('donor.dashboard', [
            'donor' => $donor,
            'totalGiven' => $totalGiven,
            'totalGivenFormatted' => $this->formatAmount($totalGiven),
            'currencyBreakdown' => $currencyBreakdown,
            'hasMultipleCurrencies' => $hasMultipleCurrencies,
            'activeSubscriptions' => $activeSubscriptions,
            'monthlyRecurring' => $monthlyRecurring,
            'monthlyRecurringFormatted' => $monthlyRecurringByCurrency,
            'monthlyDonations' => $monthlyDonations,
            'campaignBreakdown' => $campaignBreakdown,
            'recentDonations' => $recentDonations,
        ]);
    }

    public function donations()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        $totalGiven = $this->getTotalGiven($donor);
        $currencyBreakdown = $this->getCurrencyBreakdown($donor);

        return view('donor.donations', [
            'donor' => $donor,
            'totalGiven' => $totalGiven,
            'totalGivenFormatted' => $this->formatAmount($totalGiven),
            'currencyBreakdown' => $currencyBreakdown,
            'hasMultipleCurrencies' => count($currencyBreakdown) > 1,
            'donationCount' => $donor->donations()->where('status', DonationStatus::Succeeded)->count(),
            'donations' => $donor->donations()->with('campaign.organization')->latest()->paginate(10),
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
        ]);
    }

    public function cancelSubscription(string $id)
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        $subscription = $donor->subscriptions()->findOrFail($id);
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('donorportal.subscriptions')->with('success', 'Subscription cancelled.');
    }
}
