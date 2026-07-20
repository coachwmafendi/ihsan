<?php

use App\Enums\SubscriptionInterval;
use App\Models\Subscription;
use App\Services\ScheduleRetry;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->scheduleRetry = new ScheduleRetry([1, 3, 7, 7], 6);
});

it('schedules the first retry one day after failure', function (): void {
    $subscription = new Subscription([
        'retry_count' => 0,
        'failed_installment_count' => 0,
    ]);

    $result = $this->scheduleRetry->afterFailure($subscription);

    expect($result['retry_count'])->toBe(1)
        ->and($result['failed_installment_count'])->toBe(0)
        ->and($result['status'])->toBe('past_due')
        ->and($result['next_charge_at']->toDateString())->toBe(CarbonImmutable::now()->addDay()->toDateString());
});

it('increments failed_installment_count after exhausting all retries', function (): void {
    $subscription = new Subscription([
        'retry_count' => 3,
        'failed_installment_count' => 1,
    ]);

    $result = $this->scheduleRetry->afterFailure($subscription);

    expect($result['retry_count'])->toBe(0)
        ->and($result['failed_installment_count'])->toBe(2)
        ->and($result['status'])->toBe('past_due');
});

it('marks the subscription as failed after four failed installments', function (): void {
    $scheduleRetry = new ScheduleRetry([1, 3, 7, 7], 4);

    $subscription = new Subscription([
        'retry_count' => 3,
        'failed_installment_count' => 3,
    ]);

    $result = $scheduleRetry->afterFailure($subscription);

    expect($result['status'])->toBe('failed')
        ->and($result['failed_installment_count'])->toBe(4)
        ->and($result['next_charge_at'])->toBeNull();
});

it('marks the subscription as failed after injected max failed installments', function (): void {
    $subscription = new Subscription([
        'retry_count' => 3,
        'failed_installment_count' => 5,
    ]);

    $result = $this->scheduleRetry->afterFailure($subscription);

    expect($result['retry_count'])->toBe(0)
        ->and($result['failed_installment_count'])->toBe(6)
        ->and($result['status'])->toBe('failed')
        ->and($result['next_charge_at'])->toBeNull();
});

it('resets retry counters and advances the schedule after a successful charge', function (): void {
    $subscription = new Subscription([
        'interval' => SubscriptionInterval::Monthly,
    ]);

    $base = CarbonImmutable::parse('2026-01-15 10:00:00');

    $result = $this->scheduleRetry->afterSuccess($subscription, $base);

    expect($result['retry_count'])->toBe(0)
        ->and($result['failed_installment_count'])->toBe(0)
        ->and($result['status'])->toBe('active')
        ->and($result['next_charge_at']->format('Y-m-d H:i:s'))->toBe('2026-02-15 10:00:00');
});
