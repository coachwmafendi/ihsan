<?php

use Carbon\Carbon;
use Carbon\CarbonImmutable;

it('returns placeholder for null datetime', function () {
    expect(myrTime(null))->toBe('—');
});

it('formats Carbon datetime in Malaysia timezone with label', function () {
    $time = Carbon::parse('2026-06-22 06:30:00', 'UTC');

    expect(myrTime($time))->toBe('22 Jun 2026, 2:30 PM (MYT)');
});

it('formats CarbonImmutable datetime in Malaysia timezone', function () {
    $time = CarbonImmutable::parse('2026-06-22 06:30:00', 'UTC');

    expect(myrTime($time))->toBe('22 Jun 2026, 2:30 PM (MYT)');
});

it('returns formatted datetime without label', function () {
    $time = Carbon::parse('2026-06-22 06:30:00', 'UTC');

    expect(myrTime($time, withLabel: false))->toBe('22 Jun 2026, 2:30 PM');
});

it('respects custom format', function () {
    $time = Carbon::parse('2026-06-22 06:30:00', 'UTC');

    expect(myrTime($time, format: 'Y-m-d H:i'))->toBe('2026-06-22 14:30 (MYT)');
});
