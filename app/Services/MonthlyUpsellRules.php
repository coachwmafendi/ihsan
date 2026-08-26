<?php

namespace App\Services;

use App\Enums\PaymentGateway;
use App\Models\Campaign;
use App\Support\Currency;
use App\Support\MonthlyUpsellOffer;

/**
 * Turns a one-time donation amount into monthly upsell offers, using the
 * tier rules stored on the campaign under config.monthly_upsell.
 */
class MonthlyUpsellRules
{
    private const string DEFAULT_HEADING = 'Become a monthly supporter';

    private const string DEFAULT_BODY = 'Would you consider making your :amount contribution a monthly donation? Your ongoing support helps us continue our work and make a lasting impact.';

    private const string DEFAULT_DECLINE_LABEL = 'No, keep my one-time :amount gift';

    private const int DEFAULT_COOLDOWN_DAYS = 30;

    /**
     * Offers are rounded to a multiple of this so donors never see 39.6.
     */
    private const int ROUNDING_STEP = 5;

    private const int MAX_TIERS = 6;

    private const int MAX_OFFERS_PER_TIER = 2;

    public function resolve(Campaign $campaign, float $amount, string $currency): ?MonthlyUpsellOffer
    {
        $config = $campaign->config['monthly_upsell'] ?? null;

        if (! is_array($config) || ! ($config['enabled'] ?? false)) {
            return null;
        }

        if (! $campaign->allow_recurring) {
            return null;
        }

        if (! $this->supportsSubscriptions($campaign)) {
            return null;
        }

        $tier = $this->matchTier($config['tiers'] ?? [], $amount);

        if ($tier === null) {
            return null;
        }

        $offers = $this->buildOffers($tier, $amount, (float) ($campaign->minimum_amount ?? 0));

        if ($offers === []) {
            return null;
        }

        $formattedAmount = Currency::symbol($currency).' '.number_format($amount, 2);
        $heading = filled($config['heading'] ?? null) ? (string) $config['heading'] : self::DEFAULT_HEADING;
        $body = filled($config['body'] ?? null) ? (string) $config['body'] : self::DEFAULT_BODY;

        return new MonthlyUpsellOffer(
            offers: $offers,
            heading: $heading,
            body: str_replace(':amount', $formattedAmount, $body),
            declineLabel: str_replace(':amount', $formattedAmount, self::DEFAULT_DECLINE_LABEL),
            cooldownDays: (int) ($config['cooldown_days'] ?? self::DEFAULT_COOLDOWN_DAYS),
        );
    }

    /**
     * Validate admin-supplied tier rules, returning human-readable errors.
     *
     * @param  array<int, array<string, mixed>>  $tiers
     * @return array<int, string>
     */
    public function validateConfig(array $tiers): array
    {
        $errors = [];

        if (count($tiers) > self::MAX_TIERS) {
            $errors[] = 'At most '.self::MAX_TIERS.' tiers are allowed.';
        }

        $slicedTiers = array_slice(array_values($tiers), 0, self::MAX_TIERS);

        foreach ($slicedTiers as $index => $tier) {
            $label = 'Tier '.($index + 1);

            if (! is_array($tier)) {
                $errors[] = $label.' is not configured correctly.';

                continue;
            }

            $rawMin = $tier['min'] ?? null;
            $minIsNumeric = is_numeric($rawMin);

            if (! $minIsNumeric) {
                $errors[] = $label.': the minimum must be a number.';
            } else {
                $min = (float) $rawMin;

                if ($min <= 0) {
                    $errors[] = $label.': the minimum must be greater than zero.';
                }
            }

            $rawMax = $tier['max'] ?? null;
            $maxIsBlank = $rawMax === null || $rawMax === '';

            if (! $maxIsBlank && ! is_numeric($rawMax)) {
                $errors[] = $label.': the maximum must be a number.';
            } elseif ($minIsNumeric) {
                $max = $this->tierMax($tier);

                if ($max !== null && $max <= $min) {
                    $errors[] = $label.': the maximum must be greater than the minimum.';
                }
            }

            $offers = is_array($tier['offers'] ?? null) ? $tier['offers'] : [];

            if ($offers === []) {
                $errors[] = $label.': add at least one offer.';
            } elseif (count($offers) > self::MAX_OFFERS_PER_TIER) {
                $errors[] = $label.': add at most '.self::MAX_OFFERS_PER_TIER.' offers.';
            }

            foreach (array_values($offers) as $offerIndex => $offer) {
                $offerLabel = $label.', offer '.($offerIndex + 1);

                if (! is_array($offer)) {
                    $errors[] = $offerLabel.' is not configured correctly.';

                    continue;
                }

                $type = $offer['type'] ?? 'percent';

                if (! in_array($type, ['percent', 'fixed'], true)) {
                    $errors[] = $offerLabel.': choose either a percentage or a fixed amount.';

                    continue;
                }

                $rawValue = $offer['value'] ?? null;

                if (! is_numeric($rawValue)) {
                    $errors[] = $offerLabel.': the value must be a number.';

                    continue;
                }

                $value = (float) $rawValue;

                if ($type === 'fixed') {
                    if ($value <= 0) {
                        $errors[] = $offerLabel.': the amount must be greater than zero.';
                    }

                    continue;
                }

                if ($value < 1 || $value > 99) {
                    $errors[] = $offerLabel.': a percentage must be between 1 and 99.';
                }
            }

            foreach (array_slice($slicedTiers, 0, $index) as $earlierIndex => $earlier) {
                if (! is_array($earlier) || ! $this->tiersOverlap($earlier, $tier)) {
                    continue;
                }

                $errors[] = $label.' overlaps tier '.($earlierIndex + 1).'.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private function tiersOverlap(array $a, array $b): bool
    {
        $aMin = (float) ($a['min'] ?? 0);
        $bMin = (float) ($b['min'] ?? 0);
        $aMax = $this->tierMax($a) ?? INF;
        $bMax = $this->tierMax($b) ?? INF;

        return $aMin <= $bMax && $bMin <= $aMax;
    }

    /**
     * Resolve a tier's upper bound, treating a null or blank value as unbounded.
     *
     * @param  array<string, mixed>  $tier
     */
    private function tierMax(array $tier): ?float
    {
        $max = $tier['max'] ?? null;

        if ($max === null || $max === '') {
            return null;
        }

        return (float) $max;
    }

    /**
     * A CHIP campaign whose organization only enables FPX cannot charge a
     * subscription, so offering one would lead the donor into a dead end.
     */
    private function supportsSubscriptions(Campaign $campaign): bool
    {
        if ($campaign->payment_gateway !== PaymentGateway::Chip) {
            return true;
        }

        return in_array('card', $campaign->organization?->chipPaymentMethods() ?? [], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchTier(mixed $tiers, float $amount): ?array
    {
        if (! is_array($tiers)) {
            return null;
        }

        foreach ($tiers as $tier) {
            if (! is_array($tier) || ! is_numeric($tier['min'] ?? null)) {
                continue;
            }

            $min = (float) $tier['min'];
            $max = $this->tierMax($tier);

            if ($amount < $min) {
                continue;
            }

            if ($max !== null && $amount > $max) {
                continue;
            }

            return $tier;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $tier
     * @return array<int, float>
     */
    private function buildOffers(array $tier, float $amount, float $campaignMinimum): array
    {
        $offers = [];

        foreach ((is_array($tier['offers'] ?? null) ? $tier['offers'] : []) as $offer) {
            if (! is_array($offer)) {
                continue;
            }

            $value = (float) ($offer['value'] ?? 0);

            $computed = match ($offer['type'] ?? 'percent') {
                'fixed' => $value,
                'percent' => $amount * ($value / 100),
                default => null,
            };

            if ($computed === null) {
                continue;
            }

            $rounded = round($computed / self::ROUNDING_STEP) * self::ROUNDING_STEP;

            if ($rounded <= 0 || $rounded < $campaignMinimum || $rounded >= $amount) {
                continue;
            }

            $offers[] = (float) $rounded;
        }

        $offers = array_values(array_unique($offers, SORT_NUMERIC));
        sort($offers, SORT_NUMERIC);

        return array_slice($offers, 0, 2);
    }
}
