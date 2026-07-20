<?php

use App\Actions\Stripe\ChargeRecurringInstallment as ChargeStripeAction;
use App\Data\ChargeResult;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Jobs\ChargeRecurringInstallment;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
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

it('executes the charge action when a due app-controlled job runs', function (): void {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_'.uniqid(),
        'brand' => 'Visa',
        'last4' => '4242',
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
        'next_charge_at' => now()->subDay(),
        'paused_until' => null,
        'cancel_at' => null,
        'payment_count' => 1,
        'retry_count' => 0,
    ]);

    $action = Mockery::mock(ChargeStripeAction::class);
    $action->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn ($sub) => $sub->is($subscription)))
        ->andReturn(new ChargeResult('succeeded'));
    $this->instance(ChargeStripeAction::class, $action);

    $exitCode = Artisan::call('ihsan:charge-recurring-plans');

    expect($exitCode)->toBe(0);
});
