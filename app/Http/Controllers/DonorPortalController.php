<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Enums\ElementType;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Support\Currency;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class DonorPortalController extends Controller
{
    private function getDonor(Organization $organization): ?Donor
    {
        $donorId = session('donor_id');
        $organizationId = session('organization_id');

        if ($donorId === null || (string) $organizationId !== (string) $organization->getKey()) {
            return null;
        }

        return Donor::query()->find($donorId);
    }

    private function scopeToOrg($query, Organization $organization)
    {
        return $query->whereHas('campaign', fn ($query) => $query->where('organization_id', $organization->getKey()));
    }

    private function getTotalGiven(Donor $donor, Organization $organization): float
    {
        return (float) $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->sum(DB::raw('COALESCE(base_amount, gross_amount)'));
    }

    private function getCurrencyBreakdown(Donor $donor, Organization $organization): array
    {
        return $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw('currency, SUM(gross_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->mapWithKeys(function ($total, $currency) {
                return [$currency => Currency::symbol($currency).' '.number_format((float) $total, 2)];
            })
            ->toArray();
    }

    /**
     * @return array{href: string, desktop: string, mobile: string}
     */
    private function donationModalUrlsFor(?Campaign $campaign): array
    {
        if ($campaign === null) {
            $home = route('home');

            return ['href' => $home, 'desktop' => $home, 'mobile' => $home];
        }

        $elements = Element::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('is_active', true)
            ->get();

        foreach ([ElementType::Form, ElementType::Button, ElementType::Popup, ElementType::FloatingButton] as $type) {
            $element = $elements->firstWhere('type', $type);

            if ($element !== null) {
                return [
                    'href' => route('donations.show', ['element' => $element, 'popup' => 1]),
                    'desktop' => route('donations.show', ['element' => $element, 'popup' => 1]),
                    'mobile' => route('donations.show', $element),
                ];
            }
        }

        if (! $campaign->checkout_modal_enabled) {
            $home = route('home');

            return ['href' => $home, 'desktop' => $home, 'mobile' => $home];
        }

        return [
            'href' => route('donations.campaign-show', ['campaign' => $campaign, 'popup' => 1]),
            'desktop' => route('donations.campaign-show', ['campaign' => $campaign, 'popup' => 1]),
            'mobile' => route('donations.campaign-show', $campaign),
        ];
    }

    public function dashboard(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $totalGiven = $this->getTotalGiven($donor, $organization);
        $currencyBreakdown = $this->getCurrencyBreakdown($donor, $organization);

        $activeSubscriptions = $this->scopeToOrg($donor->subscriptions(), $organization)
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $activeSubscriptionsList = $this->scopeToOrg($donor->subscriptions(), $organization)
            ->where('status', SubscriptionStatus::Active)
            ->get();

        $monthlyRecurringByCurrency = $activeSubscriptionsList
            ->groupBy('currency')
            ->map(function ($subs, $currency) {
                $total = $subs->sum('amount');

                return Currency::symbol($currency).' '.number_format((float) $total, 2);
            })
            ->toArray();

        $monthlyDonations = $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw("strftime('%Y-%m', created_at) as month, SUM(COALESCE(base_amount, gross_amount)) as total")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $campaignBreakdown = $this->scopeToOrg($donor->donations(), $organization)
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

        $recentDonations = $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign.organization')
            ->latest()
            ->limit(5)
            ->get();
        $latestCampaign = $recentDonations->first()?->campaign;
        $donationModalUrls = $this->donationModalUrlsFor($latestCampaign);

        return view('donor.dashboard', [
            'donor' => $donor,
            'organization' => $organization,
            'donationModalUrl' => $donationModalUrls['href'],
            'donationModalDesktopUrl' => $donationModalUrls['desktop'],
            'donationModalMobileUrl' => $donationModalUrls['mobile'],
            'totalGiven' => $totalGiven,
            'currencyBreakdown' => $currencyBreakdown,
            'activeSubscriptions' => $activeSubscriptions,
            'monthlyRecurringFormatted' => $monthlyRecurringByCurrency,
            'monthlyDonations' => $monthlyDonations,
            'campaignBreakdown' => $campaignBreakdown,
            'recentDonations' => $recentDonations,
        ]);
    }

    public function donations(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

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

    public function subscriptions(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        return view('donor.subscriptions', [
            'donor' => $donor,
            'organization' => $organization,
            'subscriptions' => $this->scopeToOrg($donor->subscriptions(), $organization)
                ->with('campaign.organization')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function downloadAllReceipts(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

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

    public function cancelSubscription(Organization $organization, Subscription $subscription)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $subscription->loadMissing('campaign');

        if (
            $subscription->donor_id !== $donor->getKey()
            || $subscription->campaign?->organization_id !== $organization->getKey()
        ) {
            abort(403);
        }

        $subscription->update(['status' => SubscriptionStatus::Cancelled]);

        return redirect()->route('donorportal.subscriptions', $organization)
            ->with('success', 'Subscription cancelled.');
    }
}
