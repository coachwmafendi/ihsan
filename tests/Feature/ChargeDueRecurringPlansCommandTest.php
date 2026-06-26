<?php

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Jobs\ChargeRecurringInstallment;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Queue;

function createAppControlledSubscription(array $overrides = []): Subscription
{
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    return Subscription::factory()->for($campaign)->create(array_merge([
        'stripe_subscription_id' => null,
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
        'next_charge_at' => now()->subDay(),
        'paused_until' => null,
        'cancel_at' => null,
        'payment_count' => 1,
        'retry_count' => 0,
    ], $overrides));
}

it('dispatches jobs for due app-controlled subscriptions', function (): void {
    Queue::fake();

    $dueSubscription = createAppControlledSubscription();

    $exitCode = Artisan::call('ihsan:charge-recurring-plans');

    expect($exitCode)->toBe(0);

    Queue::assertPushed(ChargeRecurringInstallment::class, function ($job) use ($dueSubscription) {
        return $job->subscription->is($dueSubscription);
    });
});

it('skips legacy stripe subscriptions', function (): void {
    Queue::fake();

    createAppControlledSubscription([
        'stripe_subscription_id' => 'sub_legacy_test',
    ]);

    $exitCode = Artisan::call('ihsan:charge-recurring-plans');

    expect($exitCode)->toBe(0);

    Queue::assertNothingPushed();
});

it('skips subscriptions paused until a future date', function (): void {
    Queue::fake();

    createAppControlledSubscription([
        'paused_until' => now()->addDay(),
    ]);

    $exitCode = Artisan::call('ihsan:charge-recurring-plans');

    expect($exitCode)->toBe(0);

    Queue::assertNothingPushed();
});

it('skips subscriptions whose next charge is in the future', function (): void {
    Queue::fake();

    createAppControlledSubscription([
        'next_charge_at' => now()->addDay(),
    ]);

    $exitCode = Artisan::call('ihsan:charge-recurring-plans');

    expect($exitCode)->toBe(0);

    Queue::assertNothingPushed();
});

it('logs the number of due subscriptions and reports success', function (): void {
    Queue::fake();

    createAppControlledSubscription();
    createAppControlledSubscription();
    createAppControlledSubscription([
        'next_charge_at' => now()->addDay(),
    ]);

    $exitCode = Artisan::call('ihsan:charge-recurring-plans');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('2 due subscription(s)');
});
