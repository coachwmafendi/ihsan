<?php

use App\Actions\Chip\ChargeRecurringInstallment;
use App\Actions\Chip\ChipApiFactory;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
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

afterEach(fn () => Mockery::close());

beforeEach(function () {
    Route::post('/chip/webhook', fn () => '')->name('chip.webhook');
    Route::getRoutes()->refreshNameLookups();
});

it('creates a donation by charging the recurring token', function () {
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
        'source' => 'campaign_page',
    ]);

    $requestHistory = [];
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'INSTALL123',
            'status' => 'created',
        ])),
        new Response(200, [], json_encode([
            'id' => 'INSTALL123',
            'status' => 'paid',
        ])),
        new Response(200, [], json_encode([
            'id' => 'INSTALL123',
            'status' => 'paid',
            'transaction_data' => [
                'payment_method' => 'visa',
            ],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($requestHistory));

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')->twice()->andReturn($chipApi);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($requestHistory)->toHaveCount(3);

    $createRequest = $requestHistory[0]['request'];
    $chargeRequest = $requestHistory[1]['request'];
    $syncRequest = $requestHistory[2]['request'];

    expect($createRequest->getMethod())->toBe('POST');
    expect((string) $createRequest->getUri())->toContain('purchases/');
    $createBody = json_decode((string) $createRequest->getBody(), true);
    expect($createBody['purchase']['products'][0]['name'])->toBe($campaign->title)
        ->and($createBody['purchase']['products'][0]['price'])->toBe(5000)
        ->and($createBody['success_callback'])->toBe(route('chip.webhook'));

    expect($chargeRequest->getMethod())->toBe('POST');
    expect((string) $chargeRequest->getUri())->toContain('purchases/INSTALL123/charge/');
    $chargeBody = json_decode((string) $chargeRequest->getBody(), true);
    expect($chargeBody['recurring_token'])->toBe('RECURRING_TOKEN');

    expect($syncRequest->getMethod())->toBe('GET');
    expect((string) $syncRequest->getUri())->toContain('purchases/INSTALL123/');

    $donation = Donation::query()
        ->where('subscription_id', $subscription->id)
        ->firstOrFail();

    expect($donation->campaign_id)->toBe($campaign->id)
        ->and($donation->donor_id)->toBe($donor->id)
        ->and((float) $donation->gross_amount)->toBe(50.00)
        ->and($donation->currency)->toBe('MYR')
        ->and($donation->type)->toBe(DonationType::Recurring)
        ->and($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->chip_purchase_id)->toBe('INSTALL123')
        ->and($donation->source)->toBe('campaign_page');
});

it('works without the chip webhook route registered', function () {
    Route::partialMock()->shouldReceive('has')->with('chip.webhook')->andReturn(false);

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
        'source' => 'campaign_page',
    ]);

    $requestHistory = [];
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'INSTALL123',
            'status' => 'created',
        ])),
        new Response(200, [], json_encode([
            'id' => 'INSTALL123',
            'status' => 'paid',
        ])),
        new Response(200, [], json_encode([
            'id' => 'INSTALL123',
            'status' => 'paid',
            'transaction_data' => [
                'payment_method' => 'visa',
            ],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($requestHistory));

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')->twice()->andReturn($chipApi);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    $createBody = json_decode((string) $requestHistory[0]['request']->getBody(), true);

    expect($createBody)->not->toHaveKey('success_callback');

    $donation = Donation::query()
        ->where('subscription_id', $subscription->id)
        ->firstOrFail();

    expect($donation->status)->toBe(DonationStatus::Succeeded);
});

it('creates a pending donation when the charge is not immediately paid', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'chip_recurring_token' => 'RECURRING_TOKEN',
        'amount' => 25.00,
        'currency' => 'MYR',
        'status' => SubscriptionStatus::Active,
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'INSTALL456',
            'status' => 'created',
        ])),
        new Response(200, [], json_encode([
            'id' => 'INSTALL456',
            'status' => 'hold',
        ])),
        new Response(200, [], json_encode([
            'id' => 'INSTALL456',
            'status' => 'hold',
            'transaction_data' => [
                'payment_method' => 'visa',
            ],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')->twice()->andReturn($chipApi);

    app(ChargeRecurringInstallment::class)->handle($subscription);

    $donation = Donation::query()
        ->where('subscription_id', $subscription->id)
        ->firstOrFail();

    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->chip_purchase_id)->toBe('INSTALL456');
});

it('throws runtime exception when organization is not chip onboarded', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'chip_recurring_token' => 'RECURRING_TOKEN',
        'amount' => 25.00,
        'currency' => 'MYR',
        'status' => SubscriptionStatus::Active,
    ]);

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')
        ->once()
        ->andThrow(new InvalidArgumentException('Organization is not CHIP onboarded.'));

    expect(fn () => app(ChargeRecurringInstallment::class)->handle($subscription))
        ->toThrow(RuntimeException::class, 'Failed to initialize CHIP client');
});

it('throws runtime exception when chip api request fails', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'chip_recurring_token' => 'RECURRING_TOKEN',
        'amount' => 25.00,
        'currency' => 'MYR',
        'status' => SubscriptionStatus::Active,
    ]);

    $mockHandler = new MockHandler([
        new Response(400, [], json_encode(['error' => 'invalid_request'])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($chipApi);

    expect(fn () => app(ChargeRecurringInstallment::class)->handle($subscription))
        ->toThrow(RuntimeException::class, 'Failed to charge CHIP recurring installment');
});

it('throws runtime exception when subscription has no recurring token', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'chip_recurring_token' => null,
        'amount' => 25.00,
        'currency' => 'MYR',
        'status' => SubscriptionStatus::Active,
    ]);

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldNotReceive('make');

    expect(fn () => app(ChargeRecurringInstallment::class)->handle($subscription))
        ->toThrow(RuntimeException::class, 'Subscription does not have a CHIP recurring token');
});
