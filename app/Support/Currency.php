<?php

namespace App\Support;

class Currency
{
    private static array $symbols = [
        'myr' => 'RM',
        'usd' => '$',
        'sgd' => 'S$',
    ];

    public static function symbol(string $currency): string
    {
        return static::$symbols[strtolower($currency)] ?? strtoupper($currency);
    }

    public static function format(string $currency, float $amount): string
    {
        return strtoupper($currency).' '.number_format($amount, 2);
    }

    /**
     * Format for display copy, dropping the cents on a whole amount so donors
     * read "RM 100" rather than "RM 100.00".
     */
    public static function formatCompact(string $currency, float $amount): string
    {
        return static::symbol($currency).' '.static::compactNumber($amount);
    }

    /**
     * The number on its own, without a currency symbol.
     */
    public static function compactNumber(float $amount): string
    {
        return number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }
}
