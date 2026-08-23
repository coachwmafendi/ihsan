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
    private const DefaultHeading = 'Become a monthly supporter';

    private const DefaultBody = 'Will you convert your :amount contribution into a monthly donation? Your ongoing support can help us focus better on our work.';

    private const DefaultDeclineLabel = 'No, keep my one-time :amount gift';

    private const DefaultCooldownDays = 30;

    /**
     * The floor for any monthly offer, before the campaign minimum applies.
     */
    private const AbsoluteMinimum = 5.0;

    /**
     * Offers are rounded to a multiple of this so donors never see 39.6.
     */
    private const RoundingStep = 5;

    public function resolve(Campaign $campaign, float $amount, string $currency): ?MonthlyUpsellOffer
    {
        $config = data_get($campaign->config ?? [], 'monthly_upsell');

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

        return new MonthlyUpsellOffer(
            offers: $offers,
            heading: (string) ($config['heading'] ?? self::DefaultHeading),
            body: str_replace(':amount', $formattedAmount, (string) ($config['body'] ?? self::DefaultBody)),
            declineLabel: str_replace(':amount', $formattedAmount, self::DefaultDeclineLabel),
            cooldownDays: (int) ($config['cooldown_days'] ?? self::DefaultCooldownDays),
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
            if (! is_array($tier)) {
                continue;
            }

            $min = (float) ($tier['min'] ?? 0);
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
        $minimum = max($campaignMinimum, self::AbsoluteMinimum);
        $offers = [];

        foreach ((is_array($tier['offers'] ?? null) ? $tier['offers'] : []) as $offer) {
            if (! is_array($offer)) {
                continue;
            }

            $value = (float) ($offer['value'] ?? 0);

            $computed = match ($offer['type'] ?? 'percent') {
                'fixed' => $value,
                default => $amount * ($value / 100),
            };

            $rounded = round($computed / self::RoundingStep) * self::RoundingStep;

            if ($rounded < $minimum || $rounded >= $amount) {
                continue;
            }

            $offers[] = (float) $rounded;
        }

        $offers = array_values(array_unique($offers));
        sort($offers);

        return array_slice($offers, 0, 2);
    }
}
