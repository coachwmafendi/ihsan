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
}
