<?php

namespace App\Support;

use App\Services\GeoLocation\MaxMindGeoIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClientInfo
{
    /**
     * The specific device behind a user agent, e.g. "iPhone" or "Windows".
     *
     * Order matters: an iPod reports "iPhone OS", an iPad and an iPhone both
     * report "Mac OS X", and Android reports "Linux", so the more specific
     * patterns have to be tested first. Use Donation::deviceCategory() when the
     * coarse mobile/tablet/desktop bucket is what you need.
     */
    public static function detectDeviceType(?string $agent): string
    {
        if ($agent === null) {
            return 'desktop';
        }

        $devices = [
            'iPad' => '/iPad/i',
            'iPod' => '/iPod/i',
            'iPhone' => '/iPhone/i',
            'Android' => '/Android/i',
            'Chrome OS' => '/CrOS/i',
            'Mac' => '/Macintosh|Mac OS X/i',
            'Windows' => '/Windows/i',
            'Linux' => '/Linux|X11/i',
        ];

        foreach ($devices as $device => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $device;
            }
        }

        // Unbranded agents still tell us the form factor.
        if (preg_match('/Tablet|Silk/i', $agent)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|webOS|BlackBerry|IEMobile|Opera Mini/i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function detectBrowser(?string $agent): string
    {
        if ($agent === null) {
            return 'Unknown';
        }

        if (preg_match('/Edg\/\d+/i', $agent)) {
            return 'Edge';
        }

        if (preg_match('/OPR\/\d+|Opera\/\d+/i', $agent)) {
            return 'Opera';
        }

        if (preg_match('/Chrome\/\d+/i', $agent)) {
            return 'Chrome';
        }

        if (preg_match('/Firefox\/\d+/i', $agent)) {
            return 'Firefox';
        }

        if (preg_match('/Safari\/\d+/i', $agent)) {
            return 'Safari';
        }

        if (preg_match('/MSIE |Trident\/\d+/i', $agent)) {
            return 'Internet Explorer';
        }

        return 'Unknown';
    }

    public static function detectOs(?string $agent): string
    {
        if ($agent === null) {
            return 'Unknown';
        }

        if (preg_match('/Windows NT \d+/i', $agent)) {
            return 'Windows';
        }

        if (preg_match('/Mac OS X \d+/i', $agent)) {
            return 'macOS';
        }

        if (preg_match('/iPhone OS \d+|iPad.*OS \d+|iP(hone|od|ad).*OS \d+/i', $agent)) {
            return 'iOS';
        }

        if (preg_match('/Android \d+/i', $agent)) {
            return 'Android';
        }

        if (preg_match('/Linux/i', $agent)) {
            return 'Linux';
        }

        if (preg_match('/CrOS/i', $agent)) {
            return 'Chrome OS';
        }

        return 'Unknown';
    }

    public static function isPrivateIp(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    public static function lookupGeo(string $ip): array
    {
        if (self::isPrivateIp($ip)) {
            return [
                'geo_city' => null,
                'geo_region' => null,
            ];
        }

        $maxMind = app(MaxMindGeoIp::class)->lookup($ip);

        if ($maxMind !== null) {
            return $maxMind;
        }

        return Cache::remember(
            'geo:place:'.$ip,
            self::resolvedTtl(),
            fn (): array => self::lookupGeoFromIpApi($ip),
        );
    }

    /**
     * Resolve the ISO 3166-1 alpha-2 country code for an IP (e.g. "MY", "SG").
     *
     * MaxMind is consulted first (fast local lookup). The remote ip-api
     * fallback is only used for public IPs when MaxMind has no answer.
     */
    public static function detectCountryCode(string $ip): ?string
    {
        $country = app(MaxMindGeoIp::class)->country($ip);

        if ($country !== null) {
            return $country;
        }

        if (self::isPrivateIp($ip)) {
            return null;
        }

        // ip-api is a remote call on a three second timeout, and the checkout
        // asks for this twice per render. An address keeps its country for far
        // longer than a day, so cache the answer; cache a failure only briefly,
        // so an outage is not hammered but recovers on its own.
        $cached = Cache::get('geo:country:'.$ip);

        if ($cached !== null) {
            return $cached === '' ? null : $cached;
        }

        $country = self::countryCodeFromIpApi($ip);

        Cache::put(
            'geo:country:'.$ip,
            $country ?? '',
            $country !== null ? self::resolvedTtl() : self::unresolvedTtl(),
        );

        return $country;
    }

    private static function resolvedTtl(): int
    {
        return 60 * 60 * 24;
    }

    private static function unresolvedTtl(): int
    {
        return 60 * 10;
    }

    private static function countryCodeFromIpApi(string $ip): ?string
    {
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");

            if ($response->ok() && ($response['status'] ?? '') === 'success') {
                return $response['countryCode'] ?? null;
            }
        } catch (\Exception $e) {
            // Geo lookup failed — non-critical
        }

        return null;
    }

    /**
     * @return array{geo_city: string|null, geo_region: string|null}
     */
    private static function lookupGeoFromIpApi(string $ip): array
    {
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");

            if ($response->ok() && ($response['status'] ?? '') === 'success') {
                return [
                    'geo_city' => $response['city'] ?? null,
                    'geo_region' => $response['regionName'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Geo lookup failed — non-critical
        }

        return [
            'geo_city' => null,
            'geo_region' => null,
        ];
    }

    public static function fromRequest(Request $request): array
    {
        $agent = $request->userAgent();
        $ip = $request->ip() ?? '0.0.0.0';

        return [
            'device_type' => self::detectDeviceType($agent),
            'browser' => self::detectBrowser($agent),
            'user_agent' => $agent !== null ? mb_substr($agent, 0, 512) : null,
            'os' => self::detectOs($agent),
            'ip_address' => $ip,
            'page_url' => $request->fullUrl(),
            ...self::metaClickCookies($request),
            ...self::lookupGeo($ip),
        ];
    }

    /**
     * Read the Meta pixel's first-party cookies so server-side Conversions API
     * events carry the same browser and click identifiers as the pixel events.
     *
     * @return array{fbp: string|null, fbc: string|null}
     */
    public static function metaClickCookies(Request $request): array
    {
        $read = static function (string $name) use ($request): ?string {
            $value = $request->cookie($name);

            if (! is_string($value) || $value === '') {
                return null;
            }

            return mb_substr($value, 0, 512);
        };

        return [
            'fbp' => $read('_fbp'),
            'fbc' => $read('_fbc'),
        ];
    }
}
