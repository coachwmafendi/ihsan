<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Domains reach us in whatever shape the organiser typed them - a bare host, a
 * full URL, with or without www. Stripe registers one canonical host per
 * domain, so everything that talks to it has to agree on the same spelling.
 */
class DomainName
{
    public static function normalize(string $domain): string
    {
        $domain = trim($domain);
        $host = parse_url($domain, PHP_URL_HOST);

        if ($host) {
            $domain = $host;
        }

        $domain = strtolower($domain);

        return str_starts_with($domain, 'www.') ? substr($domain, 4) : $domain;
    }
}
