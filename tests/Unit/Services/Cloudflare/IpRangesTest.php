<?php

declare(strict_types=1);

use App\Services\Cloudflare\IpRanges;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns fallback ranges when cache is empty', function () {
    Cache::flush();

    $ranges = app(IpRanges::class)->all();

    expect($ranges)->not->toBeEmpty();
    expect($ranges)->toContain('173.245.48.0/20');
});

it('fetches and caches cloudflare ranges', function () {
    Cache::flush();

    Http::fake([
        'https://www.cloudflare.com/ips-v4' => Http::response("1.2.3.0/24\n4.5.6.0/24\n"),
        'https://www.cloudflare.com/ips-v6' => Http::response("2400:cb00::/32\n"),
    ]);

    $ranges = app(IpRanges::class)->refresh();

    expect($ranges)->toHaveCount(3)
        ->and($ranges)->toContain('1.2.3.0/24')
        ->and($ranges)->toContain('4.5.6.0/24')
        ->and($ranges)->toContain('2400:cb00::/32');

    expect(Cache::get('cloudflare.ip_ranges'))->toEqual($ranges);
});

it('falls back to defaults when cloudflare endpoints fail', function () {
    Cache::flush();

    Http::fake([
        'https://www.cloudflare.com/ips-v4' => Http::response('', 500),
        'https://www.cloudflare.com/ips-v6' => Http::response('', 500),
    ]);

    $ranges = app(IpRanges::class)->refresh();

    expect($ranges)->not->toBeEmpty();
    expect($ranges)->toContain('173.245.48.0/20');
});

it('returns cached ranges before refreshing when available', function () {
    Cache::put('cloudflare.ip_ranges', ['9.9.9.0/24'], now()->addHour());

    $ranges = app(IpRanges::class)->all();

    expect($ranges)->toEqual(['9.9.9.0/24']);
});

it('flushes cached ranges', function () {
    Cache::put('cloudflare.ip_ranges', ['9.9.9.0/24'], now()->addHour());

    app(IpRanges::class)->flush();

    expect(Cache::get('cloudflare.ip_ranges'))->toBeNull();
});
