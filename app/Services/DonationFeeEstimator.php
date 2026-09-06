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
     * Stripe Malaysia charges 3% + RM1.00 on domestic cards and FPX. A donation
     * presented in another currency is always an international card settling
     * through a conversion, which adds 1% and 2%: production balance
     * transactions settle those at 6% + RM1.00 to the cent. The fixed part is
     * that RM1.00 expressed in the presentment currency, rounded up so a moving
     * exchange rate doesn't eat into the donation. CHIP card fees run lower.
     *
     * @var array<string, array<string, array{percent: float, fixed: float}>>
     */
    private const PROCESSOR_RATES = [
        'stripe' => [
            'myr' => ['percent' => 0.030, 'fixed' => 1.00],
            'usd' => ['percent' => 0.060, 'fixed' => 0.30],
            'sgd' => ['percent' => 0.060, 'fixed' => 0.40],
        ],
        'chip' => [
            'myr' => ['percent' => 0.025, 'fixed' => 1.00],
            'usd' => ['percent' => 0.030, 'fixed' => 0.30],
            'sgd' => ['percent' => 0.030, 'fixed' => 0.50],
        ],
    ];

    private const DEFAULT_GATEWAY = 'stripe';

    private const DEFAULT_CURRENCY = 'myr';

    /**
     * Rates for the checkout script, which runs the same formula client-side.
     *
     * @return array<string, array{percent: float, fixed: float, platform: float}>
     */
    public static function rates(?string $gateway = null, ?float $platformPercent = null): array
    {
        $platform = self::platformRate($platformPercent);

        return array_map(
            fn (array $rate): array => [...$rate, 'platform' => $platform],
            self::processorRates($gateway),
        );
    }

    /**
     * What the donor adds to cover the processor and platform costs.
     *
     * @param  float|null  $platformPercent  A negotiated rate, as a percentage.
     */
    public static function estimate(float $amount, string $currency, ?string $gateway = null, ?float $platformPercent = null): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $rate = self::rateFor($gateway, $currency);
        $platform = self::platformRate($platformPercent);

        $total = ($amount + $rate['fixed'] + $platform * $amount) / (1 - $rate['percent']);

        // Round the cover up: a rounded-down cent comes out of the donation.
        return ceil(($total - $amount) * 100) / 100;
    }

    public static function fixedFee(string $currency, ?string $gateway = null): float
    {
        return self::rateFor($gateway, $currency)['fixed'];
    }

    public static function percentRate(string $currency, ?string $gateway = null): float
    {
        return self::rateFor($gateway, $currency)['percent'];
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
    private static function rateFor(?string $gateway, string $currency): array
    {
        $rates = self::processorRates($gateway);

        return $rates[strtolower($currency)] ?? $rates[self::DEFAULT_CURRENCY];
    }

    private static function platformRate(?float $platformPercent): float
    {
        $percent = $platformPercent ?? (float) config('services.stripe.processing_fee_percent', 2.5);

        return $percent / 100;
    }
}
