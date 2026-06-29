<?php

use App\Actions\Chip\ChipApiFactory;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Jobs\ChargeRecurringInstallment;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
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

afterEach(fn () => ChipApiFactory::resetFake());

it('charges a chip recurring installment via the job', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'chip_recurring_token' => 'RECURRING_TOKEN',
        'amount' => 50.00,
        'currency' => 'MYR',
        'status' => SubscriptionStatus::Active,
        'source' => 'checkout_modal',
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['id' => 'INSTALL123', 'status' => 'created'])),
        new Response(200, [], json_encode(['id' => 'INSTALL123', 'status' => 'paid'])),
        new Response(200, [], json_encode([
            'id' => 'INSTALL123',
            'status' => 'paid',
            'transaction_data' => ['payment_method' => 'visa'],
        ])),
    ]);

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => HandlerStack::create($mockHandler)],
    );
    ChipApiFactory::fake(make: fn () => $chipApi);

    (new ChargeRecurringInstallment($subscription))->handle();

    $donation = $subscription->donations()->firstOrFail();

    expect($donation->type)->toBe(DonationType::Recurring)
        ->and($donation->status)->toBe(DonationStatus::Succeeded)
        ->and((float) $donation->gross_amount)->toBe(50.00)
        ->and($donation->chip_purchase_id)->toBe('INSTALL123');
});
