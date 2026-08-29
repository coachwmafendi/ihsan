<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Models\Campaign;

/**
 * Counts how the monthly upsell performed on a campaign, from the flags the
 * donation form writes into donations.utm_params.
 *
 * Donors are counted once each: a donor who retries a failed payment produces
 * several donation rows for a single offer.
 */
class MonthlyUpsellStats
{
    /**
     * Offers shown before a conversion rate means anything. Under this, a
     * single acceptance would swing the percentage wildly.
     */
    private const int RATE_THRESHOLD = 30;

    /**
     * @return array{
     *     offers_shown: int,
     *     accepted: int,
     *     plans_started: int,
     *     added_monthly_value: float,
     *     is_approximate: bool,
     *     shows_rate: bool,
     * }
     */
    public function forCampaign(Campaign $campaign): array
    {
        $offersShown = $campaign->donations()
            ->where('utm_params->upsell_shown', true)
            ->distinct()
            ->count('donor_id');

        $accepted = $campaign->donations()
            ->where('utm_params->upsell_accepted', true)
            ->distinct()
            ->count('donor_id');

        // An acceptance only becomes revenue once the payment clears and a
        // plan exists, so these are counted separately from the acceptances.
        $plans = $campaign->donations()
            ->where('utm_params->upsell_accepted', true)
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('subscription_id')
            ->get(['currency', 'base_amount', 'gross_amount']);

        $addedMonthlyValue = 0.0;
        $isApproximate = false;

        foreach ($plans as $plan) {
            $addedMonthlyValue += (float) ($plan->base_amount ?? $plan->gross_amount);

            if (strtolower($plan->currency ?? '') !== 'myr') {
                $isApproximate = true;
            }
        }

        return [
            'offers_shown' => $offersShown,
            'accepted' => $accepted,
            'plans_started' => $plans->count(),
            'added_monthly_value' => $addedMonthlyValue,
            'is_approximate' => $isApproximate,
            'shows_rate' => $offersShown >= self::RATE_THRESHOLD,
        ];
    }
}
