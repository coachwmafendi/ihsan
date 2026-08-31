<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Counts how the monthly upsell performed on a campaign, from the flags the
 * donation form writes into donations.utm_params.
 *
 * Everything here is counted in donors, not donations: a donor who retries a
 * failed payment produces several donation rows for a single offer, and a
 * started plan is one donor moving to monthly.
 */
class MonthlyUpsellStats
{
    /**
     * Offers shown before a conversion rate means anything. Under this, a
     * single acceptance would swing the percentage wildly.
     */
    private const int RATE_THRESHOLD = 30;

    /**
     * @param  int|null  $days  Window to report on, counting back from now.
     *                          Null reports on the campaign's whole history.
     * @return array{
     *     offers_shown: int,
     *     accepted: int,
     *     plans_started: int,
     *     added_monthly_value: float,
     *     is_approximate: bool,
     *     shows_rate: bool,
     *     took_own_amount: int,
     *     took_lighter: int,
     * }
     */
    public function forCampaign(Campaign $campaign, ?int $days = null): array
    {
        $since = $days === null ? null : CarbonImmutable::now()->subDays($days);

        $offersShown = $this->donors($campaign, $since)
            ->where('utm_params->upsell_shown', true)
            ->count('donor_id');

        $accepted = $this->donors($campaign, $since)
            ->where('utm_params->upsell_accepted', true)
            ->count('donor_id');

        // Which button carried the acceptance. Without this an admin cannot
        // tell whether the lighter offer earns its place in the tier.
        $tookOwnAmount = $this->donors($campaign, $since)
            ->where('utm_params->upsell_offer_taken', 'own_amount')
            ->count('donor_id');

        $tookLighter = $this->donors($campaign, $since)
            ->where('utm_params->upsell_offer_taken', 'lighter')
            ->count('donor_id');

        // An acceptance only becomes revenue once the payment clears and a
        // plan exists, so these are counted separately from the acceptances.
        $plans = $this->scope($campaign, $since)
            ->where('utm_params->upsell_accepted', true)
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('subscription_id')
            ->get(['donor_id', 'currency', 'base_amount', 'gross_amount'])
            // One plan per donor, matching how the other figures are counted.
            ->unique('donor_id');

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
            'took_own_amount' => $tookOwnAmount,
            'took_lighter' => $tookLighter,
        ];
    }

    /**
     * @return HasMany<Donation, Campaign>
     */
    private function scope(Campaign $campaign, ?CarbonImmutable $since): HasMany
    {
        return $campaign->donations()
            ->when($since !== null, fn (Builder $query) => $query->where('donations.created_at', '>=', $since));
    }

    /**
     * @return HasMany<Donation, Campaign>
     */
    private function donors(Campaign $campaign, ?CarbonImmutable $since): HasMany
    {
        return $this->scope($campaign, $since)->distinct();
    }
}
