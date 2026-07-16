<?php

declare(strict_types=1);

namespace App\Services\Cloudflare;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpRanges
{
    private const string CACHE_KEY = 'cloudflare.ip_ranges';

    private const int CACHE_TTL_MINUTES = 10080; // 1 week

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return Cache::get(self::CACHE_KEY) ?? $this->defaults();
    }

    /**
     * Fetch current Cloudflare IP ranges and cache them.
     *
     * @return list<string>
     */
    public function refresh(): array
    {
        try {
            $ipv4 = $this->fetch('https://www.cloudflare.com/ips-v4');
            $ipv6 = $this->fetch('https://www.cloudflare.com/ips-v6');
            $ranges = array_values(array_filter([...$ipv4, ...$ipv6]));

            if ($ranges === []) {
                throw new \RuntimeException('Cloudflare returned no IP ranges.');
            }

            Cache::put(self::CACHE_KEY, $ranges, now()->addMinutes(self::CACHE_TTL_MINUTES));

            return $ranges;
        } catch (\Throwable $e) {
            Log::warning('Failed to refresh Cloudflare IP ranges.', ['error' => $e->getMessage()]);

            return $this->defaults();
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<string>
     */
    private function fetch(string $url): array
    {
        $response = Http::timeout(15)->connectTimeout(5)->get($url);

        if (! $response->ok()) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $response->body()))));
    }

    /**
     * Fallback Cloudflare IP ranges. Update with cache refresh if needed.
     *
     * @return list<string>
     */
    private function defaults(): array
    {
        return [
            '173.245.48.0/20',
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '141.101.64.0/18',
            '108.162.192.0/18',
            '190.93.240.0/20',
            '188.114.96.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '162.158.0.0/15',
            '104.16.0.0/13',
            '104.24.0.0/14',
            '172.64.0.0/13',
            '131.0.72.0/22',
            '2400:cb00::/32',
            '2606:4700::/32',
            '2803:f800::/32',
            '2405:b500::/32',
            '2405:8100::/32',
            '2a06:98c0::/29',
            '2c0f:f248::/32',
        ];
    }
}
