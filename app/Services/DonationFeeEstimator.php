<?php

declare(strict_types=1);

namespace App\Services;

final class DonationFeeEstimator
{
    /**
     * Estimated donor fee-cover rates by currency for Stripe-backed donations.
     * Combines Stripe fee + Ihsan processing fee (2.5%) + FX markup
     * for non-MYR currencies.
     *
     * @var array<string, array{percent: float, fixed: float}>
     */
    private const STRIPE_RATES = [
        'myr' => ['percent' => 0.055, 'fixed' => 1.00],
        'usd' => ['percent' => 0.069, 'fixed' => 0.30],
        'sgd' => ['percent' => 0.074, 'fixed' => 0.50],
    ];

    /**
     * Estimated donor fee-cover rates by currency for CHIP-backed donations.
     *
     * CHIP card fees are lower than Stripe (local ~2.0%, international ~3.0%).
     * We use a conservative blended rate + Ihsan processing fee (2.5%) so the
     * donor-covered amount is sufficient even if an international card is used.
     *
     * @var array<string, array{percent: float, fixed: float}>
     */
    private const CHIP_RATES = [
        'myr' => ['percent' => 0.050, 'fixed' => 1.00],
        'usd' => ['percent' => 0.055, 'fixed' => 0.30],
        'sgd' => ['percent' => 0.055, 'fixed' => 0.50],
    ];

    private const DEFAULT_GATEWAY = 'stripe';

    /**
     * @return array<string, array{percent: float, fixed: float}>
     */
    public static function rates(?string $gateway = null): array
    {
        return match (strtolower($gateway ?? self::DEFAULT_GATEWAY)) {
            'chip' => self::CHIP_RATES,
            default => self::STRIPE_RATES,
        };
    }

    public static function estimate(float $amount, string $currency, ?string $gateway = null): float
    {
        $rate = self::rates($gateway)[strtolower($currency)] ?? self::rates($gateway)['myr'];

        return round($amount * $rate['percent'] + $rate['fixed'], 2);
    }

    public static function fixedFee(string $currency, ?string $gateway = null): float
    {
        return self::rates($gateway)[strtolower($currency)]['fixed']
            ?? self::rates($gateway)['myr']['fixed'];
    }

    public static function percentRate(string $currency, ?string $gateway = null): float
    {
        return self::rates($gateway)[strtolower($currency)]['percent']
            ?? self::rates($gateway)['myr']['percent'];
    }
}
