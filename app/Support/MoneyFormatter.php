<?php

namespace App\Support;

class MoneyFormatter
{
    public static function format(float $amount, string $currency = 'MYR'): string
    {
        return $currency.' '.number_format($amount, 2);
    }

    public static function raw(float $amount): string
    {
        return number_format($amount, 2);
    }
}
