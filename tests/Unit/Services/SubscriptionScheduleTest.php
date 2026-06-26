<?php

use App\Enums\SubscriptionInterval;
use App\Services\SubscriptionSchedule;
use Carbon\CarbonImmutable;

it('returns the correct monthly factor for every interval', function (SubscriptionInterval $interval, float $expected): void {
    expect(SubscriptionSchedule::monthlyFactor($interval))->toBe($expected);
})->with([
    'weekly' => [SubscriptionInterval::Weekly, 52 / 12],
    'biweekly' => [SubscriptionInterval::Biweekly, 26 / 12],
    'monthly' => [SubscriptionInterval::Monthly, 1.0],
    'bimonthly' => [SubscriptionInterval::Bimonthly, 0.5],
    'quarterly' => [SubscriptionInterval::Quarterly, 1 / 3],
    'semiannual' => [SubscriptionInterval::Semiannual, 1 / 6],
    'yearly' => [SubscriptionInterval::Yearly, 1 / 12],
]);

it('calculates the next charge date for every interval', function (string $from, SubscriptionInterval $interval, string $expected): void {
    $next = SubscriptionSchedule::nextChargeAt(
        CarbonImmutable::parse($from),
        $interval,
    );

    expect($next->format('Y-m-d H:i:s'))->toBe($expected);
})->with([
    'weekly' => ['2026-01-15 10:00:00', SubscriptionInterval::Weekly, '2026-01-22 10:00:00'],
    'biweekly' => ['2026-01-15 10:00:00', SubscriptionInterval::Biweekly, '2026-01-29 10:00:00'],
    'monthly typical' => ['2026-01-15 10:00:00', SubscriptionInterval::Monthly, '2026-02-15 10:00:00'],
    'monthly clamps to last day of shorter month' => ['2026-01-31 10:00:00', SubscriptionInterval::Monthly, '2026-02-28 10:00:00'],
    'monthly clamps to leap day' => ['2028-01-31 10:00:00', SubscriptionInterval::Monthly, '2028-02-29 10:00:00'],
    'bimonthly' => ['2026-01-15 10:00:00', SubscriptionInterval::Bimonthly, '2026-03-15 10:00:00'],
    'quarterly' => ['2026-01-31 10:00:00', SubscriptionInterval::Quarterly, '2026-04-30 10:00:00'],
    'semiannual' => ['2026-01-15 10:00:00', SubscriptionInterval::Semiannual, '2026-07-15 10:00:00'],
    'yearly' => ['2026-01-15 10:00:00', SubscriptionInterval::Yearly, '2027-01-15 10:00:00'],
]);
