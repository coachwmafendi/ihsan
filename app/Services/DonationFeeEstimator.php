<?php

declare(strict_types=1);

namespace App\Services;

final class DonationFeeEstimator
{
    /**
     * Estimated donor fee cover rates by currency.
     * Combines Stripe fee + Ihsan processing fee (2.5%) + FX markup
     * for non-MYR currencies.
     *
     * @var array<string, array{percent: float, fixed: float}>
     */
    private const RATES = [
        'myr' => ['percent' => 0.055, 'fixed' => 1.00],
        'usd' => ['percent' => 0.069, 'fixed' => 0.30],
        'sgd' => ['percent' => 0.074, 'fixed' => 0.50],
    ];

    /**
     * @return array<string, array{percent: float, fixed: float}>
     */
    public static function rates(): array
    {
        return self::RATES;
    }

    public static function estimate(float $amount, string $currency): float
    {
        $rate = self::RATES[strtolower($currency)] ?? self::RATES['myr'];

        return round($amount * $rate['percent'] + $rate['fixed'], 2);
    }

    public static function fixedFee(string $currency): float
    {
        return self::RATES[strtolower($currency)]['fixed'] ?? self::RATES['myr']['fixed'];
    }

    public static function percentRate(string $currency): float
    {
        return self::RATES[strtolower($currency)]['percent'] ?? self::RATES['myr']['percent'];
    }
}
