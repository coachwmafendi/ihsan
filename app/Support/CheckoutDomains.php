<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The hosts Ihsan itself serves a donation form from.
 *
 * There are two. The panel domain carries the embedded checkout every NGO site
 * frames, and the main site carries the donor portal and the hosted campaign
 * pages. Only the first was ever registered with Stripe, so Apple Pay - the one
 * wallet Stripe will not show on an unregistered domain - was missing from the
 * donor portal for every organisation.
 */
class CheckoutDomains
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return collect([config('app.app_panel_domain'), config('app.url')])
            ->map(fn ($domain): string => DomainName::normalize((string) $domain))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function includes(string $host): bool
    {
        return in_array(DomainName::normalize($host), self::all(), true);
    }
}
