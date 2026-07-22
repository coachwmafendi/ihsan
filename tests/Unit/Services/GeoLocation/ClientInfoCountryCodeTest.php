<?php

declare(strict_types=1);

use App\Services\GeoLocation\MaxMindGeoIp;
use App\Support\ClientInfo;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns null country for private ip addresses when maxmind has no answer', function () {
    $this->app->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) {
        $mock->shouldReceive('country')->andReturnNull();
    }));

    Http::fake();

    expect(ClientInfo::detectCountryCode('127.0.0.1'))->toBeNull();

    Http::assertNothingSent();
});

it('uses the maxmind country code when available', function () {
    Http::fake();

    $this->app->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) {
        $mock->shouldReceive('country')
            ->with('1.2.3.4')
            ->once()
            ->andReturn('SG');
    }));

    expect(ClientInfo::detectCountryCode('1.2.3.4'))->toBe('SG');

    Http::assertNothingSent();
});

it('falls back to ip-api country code for public ips when maxmind returns null', function () {
    $this->app->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) {
        $mock->shouldReceive('country')->andReturnNull();
    }));

    Http::fake([
        'http://ip-api.com/json/*' => Http::response([
            'status' => 'success',
            'countryCode' => 'US',
        ]),
    ]);

    expect(ClientInfo::detectCountryCode('1.2.3.4'))->toBe('US');
});
