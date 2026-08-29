<?php

namespace App\Services;

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

        return [
            'offers_shown' => $offersShown,
            'accepted' => 0,
            'plans_started' => 0,
            'added_monthly_value' => 0.0,
            'is_approximate' => false,
            'shows_rate' => false,
        ];
    }
}
