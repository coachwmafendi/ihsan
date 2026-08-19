<?php

declare(strict_types=1);

use App\Models\Donation;

it('maps device types to a device category', function () {
    expect(Donation::factory()->make(['device_type' => 'iPhone'])->deviceCategory())->toBe('mobile')
        ->and(Donation::factory()->make(['device_type' => 'Android'])->deviceCategory())->toBe('mobile')
        ->and(Donation::factory()->make(['device_type' => 'mobile'])->deviceCategory())->toBe('mobile')
        ->and(Donation::factory()->make(['device_type' => 'iPad'])->deviceCategory())->toBe('tablet')
        ->and(Donation::factory()->make(['device_type' => 'tablet'])->deviceCategory())->toBe('tablet')
        ->and(Donation::factory()->make(['device_type' => 'Mac'])->deviceCategory())->toBe('desktop')
        ->and(Donation::factory()->make(['device_type' => 'Windows'])->deviceCategory())->toBe('desktop')
        ->and(Donation::factory()->make(['device_type' => 'desktop'])->deviceCategory())->toBe('desktop')
        ->and(Donation::factory()->make(['device_type' => null])->deviceCategory())->toBe('desktop');
});

it('extracts utm parameters from the stored utm_params json', function () {
    $donation = Donation::factory()->make([
        'utm_params' => [
            'utm_source' => 'fb',
            'utm_medium' => 'paid',
            'utm_campaign' => '120250810002650199',
            'utm_term' => '120250810002680199',
            'utm_content' => '120250810002670199',
        ],
    ]);

    expect($donation->utm_parameters)->toBe([
        'source' => 'fb',
        'medium' => 'paid',
        'campaign' => '120250810002650199',
        'term' => '120250810002680199',
        'content' => '120250810002670199',
    ]);
});

it('returns null utm parameters when utm_params has none', function () {
    $donation = Donation::factory()->make(['utm_params' => ['source' => 'element']]);

    expect($donation->utm_parameters)->toBe([
        'source' => null,
        'medium' => null,
        'campaign' => null,
        'term' => null,
        'content' => null,
    ]);
});
