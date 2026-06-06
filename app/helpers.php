<?php

use App\Support\MoneyFormatter;

if (! function_exists('money')) {
    function money(float $amount, string $currency = 'MYR'): string
    {
        return MoneyFormatter::format($amount, $currency);
    }
}

if (! function_exists('money_raw')) {
    function money_raw(float $amount): string
    {
        return MoneyFormatter::raw($amount);
    }
}
