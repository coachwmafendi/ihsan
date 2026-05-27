<?php

namespace App\Livewire\DonorPortal;

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donor;
use App\Support\Currency;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public Donor $donor;

    public function mount(): void
    {
        $donorId = session('donor_id');

        $this->donor = Donor::query()->findOrFail($donorId);
    }

    public function placeholder(): View
    {
        return view('livewire.donor-portal.placeholders.dashboard');
    }

    public function render(): View
    {
        $totalGiven = (float) $this->donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->sum(\DB::raw('COALESCE(base_amount, gross_amount)'));

        $currencyBreakdown = $this->donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw('currency, SUM(gross_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->mapWithKeys(fn ($total, $currency) => [$currency => Currency::symbol($currency).' '.number_format((float) $total, 2)])
            ->toArray();

        $activeSubscriptions = $this->donor->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $activeSubscriptionsList = $this->donor->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->get();

        $monthlyRecurringByCurrency = $activeSubscriptionsList
            ->groupBy('currency')
            ->map(fn ($subs, $currency) => Currency::symbol($currency).' '.number_format((float) $subs->sum('amount'), 2))
            ->toArray();

        $monthlyDonations = $this->donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw("strftime('%Y-%m', created_at) as month, SUM(COALESCE(base_amount, gross_amount)) as total")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $campaignBreakdown = $this->donor->donations()
            ->where('donations.status', DonationStatus::Succeeded)
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.title as campaign, donations.currency, SUM(donations.gross_amount) as total')
            ->groupBy('campaigns.title', 'donations.currency')
            ->orderByDesc('total')
            ->get()
            ->groupBy('campaign')
            ->map(fn ($items) => $items->mapWithKeys(fn ($item) => [$item->currency => Currency::symbol($item->currency).' '.number_format((float) $item->total, 2)]));

        $recentDonations = $this->donor->donations()
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign.organization')
            ->latest()
            ->limit(5)
            ->get();

        $primaryOrganization = $recentDonations->first()?->campaign?->organization;

        return view('donor.dashboard', [
            'donor' => $this->donor,
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
}
