<?php

declare(strict_types=1);

use App\Http\Middleware\TrustCloudflare;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->middleware = app(TrustCloudflare::class);
});

it('returns real client ip for requests coming from cloudflare', function () {
    $request = Request::create(
        'https://ihsan.test/app/dashboard',
        'GET',
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => '173.245.48.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]
    );

    $handled = false;
    $this->middleware->handle($request, function (Request $request) use (&$handled): Response {
        $handled = true;

        expect($request->ip())->toBe('1.2.3.4')
            ->and($request->isSecure())->toBeTrue();

        return new Response('ok');
    });

    expect($handled)->toBeTrue();
});

it('does not trust forwarded headers from non-cloudflare sources', function () {
    $request = Request::create(
        'http://ihsan.test/app/dashboard',
        'GET',
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => '203.0.113.1',
            'HTTP_X_FORWARDED_FOR' => '9.9.9.9',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]
    );

    $this->middleware->handle($request, function (Request $request): Response {
        expect($request->ip())->toBe('203.0.113.1')
            ->and($request->isSecure())->toBeFalse();

        return new Response('ok');
    });
});

it('supports cloudflare ipv6 ranges', function () {
    $request = Request::create(
        'https://ihsan.test/app/dashboard',
        'GET',
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => '2400:cb00::1',
            'HTTP_X_FORWARDED_FOR' => '2001:db8::1',
        ]
    );

    $this->middleware->handle($request, function (Request $request): Response {
        expect($request->ip())->toBe('2001:db8::1');

        return new Response('ok');
    });
});

it('uses fallback ranges when cache is empty', function () {
    Cache::flush();

    $request = Request::create(
        'https://ihsan.test/app/dashboard',
        'GET',
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => '104.16.0.1',
            'HTTP_X_FORWARDED_FOR' => '5.5.5.5',
        ]
    );

    $this->middleware->handle($request, function (Request $request): Response {
        expect($request->ip())->toBe('5.5.5.5');

        return new Response('ok');
    });
});

it('uses CF-Connecting-IP when behind an internal load balancer', function () {
    $request = Request::create(
        'https://ihsan.test/app/dashboard',
        'GET',
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => '10.0.1.6',
            'HTTP_X_FORWARDED_FOR' => '172.69.176.144',
            'HTTP_CF_CONNECTING_IP' => '2001:e68:5472:8c18:14a0:73f1:4f32:5ab5',
        ]
    );

    $this->middleware->handle($request, function (Request $request): Response {
        expect($request->ip())->toBe('2001:e68:5472:8c18:14a0:73f1:4f32:5ab5');

        return new Response('ok');
    });
});
