<?php

declare(strict_types=1);

use App\Services\GeoLocation\MaxMindGeoIp;
use Tests\TestCase;

uses(TestCase::class);

it('returns null when database file does not exist', function () {
    $service = new MaxMindGeoIp('/path/that/does/not/exist.mmdb');

    expect($service->lookup('1.2.3.4'))->toBeNull();
});
