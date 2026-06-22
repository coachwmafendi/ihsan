<?php

use App\Support\MoneyFormatter;
use Illuminate\Support\Carbon;

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

if (! function_exists('myrTime')) {
    function myrTime(?Carbon $datetime, bool $withLabel = true, string $format = 'd M Y, h:i A'): string
    {
        if (! $datetime) {
            return '—';
        }

        $time = $datetime->timezone('Asia/Kuala_Lumpur')->format($format);

        return $withLabel ? "{$time} (MYT)" : $time;
    }
}
