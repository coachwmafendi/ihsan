<?php

declare(strict_types=1);

use App\Services\GeoLocation\MaxMindGeoIp;
use App\Support\ClientInfo;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns null location for private ip addresses', function () {
    expect(ClientInfo::lookupGeo('127.0.0.1'))->toEqual([
        'geo_city' => null,
        'geo_region' => null,
    ]);
});

it('uses maxmind result when database is available', function () {
    Http::fake();

    $this->app->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) {
        $mock->shouldReceive('lookup')
            ->with('1.2.3.4')
            ->once()
            ->andReturn([
                'geo_city' => 'Kuala Lumpur',
                'geo_region' => 'Kuala Lumpur',
            ]);
    }));

    expect(ClientInfo::lookupGeo('1.2.3.4'))->toEqual([
        'geo_city' => 'Kuala Lumpur',
        'geo_region' => 'Kuala Lumpur',
    ]);
});

it('falls back to ip-api when maxmind returns null', function () {
    $this->app->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) {
        $mock->shouldReceive('lookup')->andReturnNull();
    }));

    Http::fake([
        'http://ip-api.com/json/*' => Http::response([
            'status' => 'success',
            'city' => 'George Town',
            'regionName' => 'Penang',
        ]),
    ]);

    expect(ClientInfo::lookupGeo('1.2.3.4'))->toEqual([
        'geo_city' => 'George Town',
        'geo_region' => 'Penang',
    ]);
});

it('falls back to ip-api when maxmind lookup fails', function () {
    $this->app->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) {
        $mock->shouldReceive('lookup')->andReturnNull();
    }));

    Http::fake([
        'http://ip-api.com/json/*' => Http::response([
            'status' => 'success',
            'city' => 'Ipoh',
            'regionName' => 'Perak',
        ]),
    ]);

    expect(ClientInfo::lookupGeo('1.2.3.4'))->toEqual([
        'geo_city' => 'Ipoh',
        'geo_region' => 'Perak',
    ]);
});

it('returns null location when both maxmind and ip-api fail', function () {
    $this->app->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) {
        $mock->shouldReceive('lookup')->andReturnNull();
    }));

    Http::fake(['*' => Http::response('', 500)]);

    expect(ClientInfo::lookupGeo('1.2.3.4'))->toEqual([
        'geo_city' => null,
        'geo_region' => null,
    ]);
});
