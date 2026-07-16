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
}
