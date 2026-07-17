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

if (! function_exists('appPanelRoute')) {
    /**
     * Absolute URL for a named route, always on the app panel domain. Use in
     * emails and queued jobs where the request host (or APP_URL fallback)
     * would otherwise point at the landing or admin domain.
     */
    function appPanelRoute(string $name, mixed $parameters = []): string
    {
        $root = ($domain = config('app.app_panel_domain'))
            ? 'https://'.$domain
            : rtrim(config('app.url'), '/');

        return $root.route($name, $parameters, false);
    }
}

if (! function_exists('app_panel_port_suffix')) {
    /**
     * Port suffix for redirects onto the app panel domain. Empty on standard
     * ports; keeps non-standard local dev ports (e.g. Herd's 8443) working.
     */
    function app_panel_port_suffix(): string
    {
        $port = request()->getPort();

        return in_array($port, [80, 443], true) ? '' : ':'.$port;
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
