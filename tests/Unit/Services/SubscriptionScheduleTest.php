<?php

use App\Enums\SubscriptionInterval;
use App\Services\SubscriptionSchedule;
use Carbon\CarbonImmutable;

it('calculates monthly from a typical day', function (): void {
    $next = SubscriptionSchedule::nextChargeAt(
        CarbonImmutable::parse('2026-01-15 10:00:00'),
        SubscriptionInterval::Monthly,
    );

    expect($next->format('Y-m-d H:i:s'))->toBe('2026-02-15 10:00:00');
});

it('clamps month-end dates to last day of shorter months', function (): void {
    $next = SubscriptionSchedule::nextChargeAt(
        CarbonImmutable::parse('2026-01-31 10:00:00'),
        SubscriptionInterval::Monthly,
    );

    expect($next->format('Y-m-d'))->toBe('2026-02-28');
});

it('calculates quarterly schedule', function (): void {
    $next = SubscriptionSchedule::nextChargeAt(
        CarbonImmutable::parse('2026-01-31 10:00:00'),
        SubscriptionInterval::Quarterly,
    );

    expect($next->format('Y-m-d'))->toBe('2026-04-30');
});
