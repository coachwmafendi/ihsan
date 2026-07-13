<?php

use App\Support\MoneyFormatter;
use Carbon\CarbonInterface;

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
    function myrTime(?CarbonInterface $datetime, bool $withLabel = true, string $format = 'd M Y, g:i A'): string
    {
        if (! $datetime) {
            return '—';
        }

        $time = $datetime->timezone('Asia/Kuala_Lumpur')->format($format);

        return $withLabel ? "{$time} (MYT)" : $time;
    }
}

if (! function_exists('app_host')) {
    function app_host(): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        return $host ?: 'localhost';
    }
}

if (! function_exists('support_email')) {
    function support_email(): string
    {
        return 'support@'.app_host();
    }
}

if (! function_exists('noreply_email')) {
    function noreply_email(): string
    {
        return config('mail.from.address', 'no-reply@'.app_host());
    }
}
