<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Cloudflare\IpRanges;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class TrustCloudflare
{
    public function __construct(private IpRanges $ipRanges) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headers = Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO;

        $remoteAddr = $request->server->get('REMOTE_ADDR');

        // Cloudflare passes the real visitor IP in CF-Connecting-IP. If the
        // request reaches us through an internal load balancer we may also need
        // to trust private ranges, otherwise Laravel ignores X-Forwarded-For.
        if ($this->shouldUseCloudflareConnectingIp($request, $remoteAddr)) {
            $request->headers->set('X-Forwarded-For', $request->header('CF-Connecting-IP'));
        }

        $trustedProxies = [
            ...$this->ipRanges->all(),
            ...$this->internalRanges(),
        ];

        $request->setTrustedProxies($trustedProxies, $headers);

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function internalRanges(): array
    {
        return [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            'fc00::/7',
            '::1',
        ];
    }

    private function shouldUseCloudflareConnectingIp(Request $request, ?string $remoteAddr): bool
    {
        if (! $request->hasHeader('CF-Connecting-IP')) {
            return false;
        }

        $connectingIp = $request->header('CF-Connecting-IP');

        if (! is_string($connectingIp) || $connectingIp === '') {
            return false;
        }

        // Direct Cloudflare edge or internal load balancer in front of Cloudflare.
        return IpUtils::checkIp($remoteAddr ?? '', [
            ...$this->ipRanges->all(),
            ...$this->internalRanges(),
        ]);
    }
}
