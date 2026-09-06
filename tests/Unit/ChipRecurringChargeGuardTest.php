<?php

use App\Actions\Chip\ChargeRecurringInstallment;
use App\Actions\Chip\ChipApiFactory;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function () {
    ChipApiFactory::resetFake();
    Route::post('/chip/webhook', fn () => '')->name('chip.webhook');
    Route::getRoutes()->refreshNameLookups();
});

afterEach(function () {
    ChipApiFactory::resetFake();
    Mockery::close();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function chipSubscription(array $attributes = []): Subscription
{
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    return Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'chip_recurring_token' => 'RECURRING_TOKEN',
        'amount' => 50.00,
        'currency' => 'MYR',
        'status' => SubscriptionStatus::Active,
        ...$attributes,
    ]);
}

/**
 * @param  array<int, mixed>  $history
 */
function fakePaidChipApi(array &$history): void
{
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['id' => 'INSTALL123', 'status' => 'created'])),
        new Response(200, [], json_encode(['id' => 'INSTALL123', 'status' => 'paid'])),
        new Response(200, [], json_encode(['id' => 'INSTALL123', 'status' => 'paid'])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($history));

    ChipApiFactory::fake(make: fn () => new ChipApi(
        brandId: 'BRAND123',
        apiKey: 'secret',
        config: ['handler' => $handlerStack],
    ));
}

it('moves next_charge_at forward after a successful installment', function () {
    $subscription = chipSubscription(['next_charge_at' => now()->subMinute()]);
    $paymentCountBefore = (int) $subscription->fresh()->payment_count;

    $history = [];
    fakePaidChipApi($history);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    $subscription->refresh();

    expect($subscription->next_charge_at)->not->toBeNull()
        ->and($subscription->next_charge_at->isFuture())->toBeTrue()
        ->and($subscription->last_charge_at)->not->toBeNull()
        ->and((int) $subscription->payment_count)->toBe($paymentCountBefore + 1);
});

it('does not charge again while the subscription is not due yet', function () {
    $subscription = chipSubscription(['next_charge_at' => now()->addWeek()]);

    $history = [];
    fakePaidChipApi($history);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($history)->toHaveCount(0)
        ->and(Donation::query()->where('subscription_id', $subscription->id)->count())->toBe(0);
});

it('does not charge a subscription that is no longer active', function () {
    $subscription = chipSubscription([
        'status' => SubscriptionStatus::Cancelled,
        'next_charge_at' => now()->subMinute(),
    ]);

    $history = [];
    fakePaidChipApi($history);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($history)->toHaveCount(0)
        ->and(Donation::query()->where('subscription_id', $subscription->id)->count())->toBe(0);
});

it('does not charge a paused subscription', function () {
    $subscription = chipSubscription([
        'next_charge_at' => now()->subMinute(),
        'paused_until' => now()->addMonth(),
    ]);

    $history = [];
    fakePaidChipApi($history);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($history)->toHaveCount(0)
        ->and(Donation::query()->where('subscription_id', $subscription->id)->count())->toBe(0);
});

it('charges only once when the same installment is dispatched twice', function () {
    $subscription = chipSubscription(['next_charge_at' => now()->subMinute()]);

    $history = [];
    fakePaidChipApi($history);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    // A retried queue job still holds the stale model it was serialised with.
    app(ChargeRecurringInstallment::class)->handle($subscription);

    expect(Donation::query()->where('subscription_id', $subscription->id)->count())->toBe(1);
});
