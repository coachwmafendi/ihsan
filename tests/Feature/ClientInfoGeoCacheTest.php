<?php

declare(strict_types=1);

use App\Support\ClientInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The donation form resolves the visitor's country twice per render - once to
 * pick the currency and once to price the fee cover - and ip-api is a remote
 * call with a three second timeout. Without a cache, a burst of traffic pays
 * that cost on every request, and the checkout is the worst place to spend it.
 */
beforeEach(function () {
    Cache::flush();
});

it('calls the geo service once for repeat visits from the same address', function () {
    Http::fake([
        'ip-api.com/*' => Http::response(['status' => 'success', 'countryCode' => 'SG'], 200),
    ]);

    expect(ClientInfo::detectCountryCode('118.201.0.1'))->toBe('SG')
        ->and(ClientInfo::detectCountryCode('118.201.0.1'))->toBe('SG')
        ->and(ClientInfo::detectCountryCode('118.201.0.1'))->toBe('SG');

    Http::assertSentCount(1);
});

it('keeps separate answers for separate addresses', function () {
    Http::fake([
        'ip-api.com/json/118.201.0.1*' => Http::response(['status' => 'success', 'countryCode' => 'SG'], 200),
        'ip-api.com/json/175.139.0.1*' => Http::response(['status' => 'success', 'countryCode' => 'MY'], 200),
    ]);

    expect(ClientInfo::detectCountryCode('118.201.0.1'))->toBe('SG')
        ->and(ClientInfo::detectCountryCode('175.139.0.1'))->toBe('MY')
        ->and(ClientInfo::detectCountryCode('118.201.0.1'))->toBe('SG');

    Http::assertSentCount(2);
});

it('does not hammer the geo service while it is failing', function () {
    Http::fake([
        'ip-api.com/*' => Http::response(['status' => 'fail'], 500),
    ]);

    expect(ClientInfo::detectCountryCode('118.201.0.1'))->toBeNull()
        ->and(ClientInfo::detectCountryCode('118.201.0.1'))->toBeNull();

    Http::assertSentCount(1);
});

it('never calls out for a private address', function () {
    Http::fake();

    expect(ClientInfo::detectCountryCode('127.0.0.1'))->toBeNull()
        ->and(ClientInfo::detectCountryCode('192.168.1.10'))->toBeNull();

    Http::assertNothingSent();
});

it('caches the city and region lookup too', function () {
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'city' => 'Singapore',
            'regionName' => 'Central Singapore',
        ], 200),
    ]);

    $first = ClientInfo::lookupGeo('118.201.0.1');
    $second = ClientInfo::lookupGeo('118.201.0.1');

    expect($first)->toBe($second)
        ->and($first['geo_city'])->toBe('Singapore');

    Http::assertSentCount(1);
});
