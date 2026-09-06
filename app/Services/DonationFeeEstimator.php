<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Work out what a donor has to add so the organization still receives the
 * amount they meant to give.
 *
 * Adding the fee percentages to the donation undershoots, because the
 * processor charges its percentage on the grossed-up total rather than on the
 * donation. Solving for the total instead:
 *
 *     total = (donation + fixed + platform x donation) / (1 - processor)
 *
 * The platform fee is charged on the donation itself, so it stays in the
 * numerator; the processor fee is charged on the total, so it drives the
 * denominator.
 *
 * The result is an estimate. The real processor fee depends on the card the
 * donor ends up using - an international card or a currency conversion costs
 * more - so the checkout presents this as approximate.
 */
final class DonationFeeEstimator
{
    /**
     * Processor fees by gateway and currency, excluding the platform fee.
     *
     * Stripe Malaysia charges 3% on a Malaysian card, adds 1% when the card was
     * issued abroad and 2% when the charge has to be converted, plus RM1.00.
     * Production settlements confirm every combination to within 0.03%: a
     * ringgit charge on a Malaysian card lands at 3%, on a foreign card at 4%,
     * and a foreign-currency charge at 5% domestic or 6% foreign.
     *
     * The fixed part is that RM1.00 expressed in the presentment currency,
     * rounded up so a moving exchange rate doesn't eat into the donation.
     *
     * CHIP's rates are unmeasured and the gateway is locked; see
     * docs/guides/chip-v2.md.
     *
     * @var array<string, array<string, array{percent: float, international: float, fixed: float}>>
     */
    private const PROCESSOR_RATES = [
        'stripe' => [
            'myr' => ['percent' => 0.030, 'international' => 0.040, 'fixed' => 1.00],
            'usd' => ['percent' => 0.050, 'international' => 0.060, 'fixed' => 0.30],
            'sgd' => ['percent' => 0.050, 'international' => 0.060, 'fixed' => 0.40],
        ],
        'chip' => [
            'myr' => ['percent' => 0.025, 'international' => 0.025, 'fixed' => 1.00],
            'usd' => ['percent' => 0.030, 'international' => 0.030, 'fixed' => 0.30],
            'sgd' => ['percent' => 0.030, 'international' => 0.030, 'fixed' => 0.50],
        ],
    ];

    private const DEFAULT_GATEWAY = 'stripe';

    private const DEFAULT_CURRENCY = 'myr';

    /**
     * Rates for the checkout script, which runs the same formula client-side.
     *
     * @param  string|null  $donorCountry  Where the donor appears to be, used to
     *                                     decide whether the card is foreign.
     * @return array<string, array{percent: float, fixed: float, platform: float}>
     */
    public static function rates(?string $gateway = null, ?float $platformPercent = null, ?string $donorCountry = null): array
    {
        $platform = self::platformRate($platformPercent);
        $international = self::isInternational($donorCountry);

        return array_map(
            fn (array $rate): array => [
                'percent' => $international ? $rate['international'] : $rate['percent'],
                'fixed' => $rate['fixed'],
                'platform' => $platform,
            ],
            self::processorRates($gateway),
        );
    }

    /**
     * What the donor adds to cover the processor and platform costs.
     *
     * @param  float|null  $platformPercent  A negotiated rate, as a percentage.
     */
    public static function estimate(float $amount, string $currency, ?string $gateway = null, ?float $platformPercent = null, ?string $donorCountry = null): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $rate = self::rateFor($gateway, $currency, $donorCountry);
        $platform = self::platformRate($platformPercent);

        $total = ($amount + $rate['fixed'] + $platform * $amount) / (1 - $rate['percent']);

        // Round the cover up: a rounded-down cent comes out of the donation.
        return ceil(($total - $amount) * 100) / 100;
    }

    public static function fixedFee(string $currency, ?string $gateway = null): float
    {
        return self::rateFor($gateway, $currency)['fixed'];
    }

    public static function percentRate(string $currency, ?string $gateway = null, ?string $donorCountry = null): float
    {
        return self::rateFor($gateway, $currency, $donorCountry)['percent'];
    }

    /**
     * A card is treated as foreign unless the donor looks to be in the same
     * country as the Stripe account. An unresolved country counts as foreign:
     * guessing domestic would leave the organization short, while guessing
     * foreign only costs that donor a little extra.
     */
    private static function isInternational(?string $donorCountry): bool
    {
        $accountCountry = strtoupper((string) config('services.stripe.account_country', 'MY'));

        return $donorCountry === null || strtoupper($donorCountry) !== $accountCountry;
    }

    /**
     * @return array<string, array{percent: float, fixed: float}>
     */
    private static function processorRates(?string $gateway): array
    {
        $key = strtolower($gateway ?? self::DEFAULT_GATEWAY);

        return self::PROCESSOR_RATES[$key] ?? self::PROCESSOR_RATES[self::DEFAULT_GATEWAY];
    }

    /**
     * @return array{percent: float, fixed: float}
     */
    private static function rateFor(?string $gateway, string $currency, ?string $donorCountry = null): array
    {
        $rates = self::processorRates($gateway);
        $rate = $rates[strtolower($currency)] ?? $rates[self::DEFAULT_CURRENCY];

        return [
            'percent' => self::isInternational($donorCountry) ? $rate['international'] : $rate['percent'],
            'fixed' => $rate['fixed'],
        ];
    }

    private static function platformRate(?float $platformPercent): float
    {
        $percent = $platformPercent ?? (float) config('services.stripe.processing_fee_percent', 2.5);

        return $percent / 100;
    }
}
