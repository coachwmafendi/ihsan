<?php

declare(strict_types=1);

namespace App\Support;

class Browser
{
    public static function parse(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'Unknown';
        }

        $ua = strtolower($userAgent);

        $browser = match (true) {
            str_contains($ua, 'edg') => 'Edge',
            str_contains($ua, 'opr') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'chrome') => 'Chrome',
            str_contains($ua, 'safari') => 'Safari',
            str_contains($ua, 'firefox') => 'Firefox',
            default => 'Browser',
        };

        $os = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'iphone') => 'iOS',
            str_contains($ua, 'ipad') => 'iPadOS',
            str_contains($ua, 'macintosh') || str_contains($ua, 'mac os') => 'macOS',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Unknown OS',
        };

        return "{$browser} / {$os}";
    }
}
