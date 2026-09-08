<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Enums\ElementType;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Support\Currency;
use App\Support\DateExpression;

class DonorDashboardController extends Controller
{
    use DonorPortalScoping;

    /**
     * The checkout the "Make a new donation" button opens.
     *
     * Phones used to be sent to the full hosted page instead of the popup one,
     * which put an entire website - header, footer and all - inside a modal
     * that already had a frame of its own. The popup layout strips its own
     * chrome when it detects it is in an iframe, so one URL serves both.
     */
    private function donationModalUrlFor(?Campaign $campaign): string
    {
        if ($campaign === null) {
            return route('home');
        }

        $portalElement = Element::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('is_active', true)
            ->where('is_donor_portal_default', true)
            ->first();

        if ($portalElement !== null) {
            return route('donations.show', ['element' => $portalElement, 'popup' => 1]);
        }

        $elements = Element::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('is_active', true)
            ->get();

        foreach ([ElementType::Form, ElementType::Button, ElementType::Popup, ElementType::FloatingButton, ElementType::StickyButton, ElementType::QrCode, ElementType::Link] as $type) {
            $element = $elements->firstWhere('type', $type);

            if ($element !== null) {
                return route('donations.show', ['element' => $element, 'popup' => 1]);
            }
        }

        if (! $campaign->checkout_modal_enabled) {
            return route('home');
        }

        return route('donations.campaign-show', ['campaign' => $campaign, 'popup' => 1]);
    }

    public function dashboard(Organization $organization)
    {
        $donor = request()->donor;

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
            ->selectRaw(DateExpression::monthlyGroup('created_at').' as month, SUM(COALESCE(base_amount, gross_amount)) as total')
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
                    return [$item->currency => Currency::symbol($item->currency).' '.number_format((float) $item->total, 2)];
                });
            });

        $campaignChartData = $this->scopeToOrg($donor->donations(), $organization)
            ->where('donations.status', DonationStatus::Succeeded)
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.title as campaign, SUM(donations.gross_amount) as total')
            ->groupBy('campaigns.title')
            ->orderByDesc('total')
            ->get()
            ->values();

        $recentDonations = $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign.organization')
            ->latest()
            ->limit(5)
            ->get();
        $latestCampaign = $recentDonations->first()?->campaign;

        return view('donor.dashboard', [
            'donor' => $donor,
            'organization' => $organization,
            'donationModalUrl' => $this->donationModalUrlFor($latestCampaign),
            'donationModalIframeAllow' => EmbedCheckoutController::IframeAllow,
            'totalGiven' => $totalGiven,
            'currencyBreakdown' => $currencyBreakdown,
            'activeSubscriptions' => $activeSubscriptions,
            'monthlyRecurringFormatted' => $monthlyRecurringByCurrency,
            'monthlyDonations' => $monthlyDonations,
            'campaignBreakdown' => $campaignBreakdown,
            'campaignChartData' => $campaignChartData,
            'recentDonations' => $recentDonations,
        ]);
    }
}
