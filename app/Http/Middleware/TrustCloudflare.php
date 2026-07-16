<?php

namespace App\Http\Middleware;

use App\Services\Cloudflare\IpRanges;
use Closure;
use Illuminate\Http\Request;
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

        $request->setTrustedProxies($this->ipRanges->all(), $headers);

        return $next($request);
    }
}
