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

    private const string DEFAULT_BODY = 'Will you convert your :amount contribution into a monthly donation? Your ongoing support can help us focus better on our work.';

    private const string DEFAULT_DECLINE_LABEL = 'No, keep my one-time :amount gift';

    private const int DEFAULT_COOLDOWN_DAYS = 30;

    /**
     * Offers are rounded to a multiple of this so donors never see 39.6.
     */
    private const int ROUNDING_STEP = 5;

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
            $max = $tier['max'] ?? null;

            if ($amount < $min) {
                continue;
            }

            if ($max !== null && $max !== '' && $amount > (float) $max) {
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
