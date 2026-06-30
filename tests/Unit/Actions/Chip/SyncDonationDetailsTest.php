<?php

use App\Actions\Chip\ChipApiFactory;
use App\Actions\Chip\SyncDonationDetails;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(fn () => ChipApiFactory::resetFake());

afterEach(function () {
    ChipApiFactory::resetFake();
    Mockery::close();
});

it('returns early when chip_purchase_id is blank', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'chip_purchase_id' => null,
    ]);

    ChipApiFactory::fake(make: fn () => throw new RuntimeException('CHIP client should not be created'));

    app(SyncDonationDetails::class)->sync($donation);

    expect($donation->fresh()->status)->toBe($donation->getOriginal('status'));
    expect(ProcessingFee::where('donation_id', $donation->id)->exists())->toBeFalse();
});

it('syncs chip donation details and creates processing fee', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'currency' => 'MYR',
        'gross_amount' => 100.00,
        'chip_purchase_id' => 'PURCHASE123',
    ]);

    $requestHistory = [];
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
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

    ChipApiFactory::fake(make: fn () => $chipApi);

    app(SyncDonationDetails::class)->sync($donation);

    $donation = $donation->fresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->processing_fee)->toBe('2.50')
        ->and($donation->net_amount)->toBe('97.50')
        ->and($donation->payment_method_brand)->toBe('visa');

    expect(ProcessingFee::where('donation_id', $donation->id)->exists())->toBeTrue();

    expect($requestHistory)->toHaveCount(1);
    expect($requestHistory[0]['request']->getMethod())->toBe('GET');
    expect((string) $requestHistory[0]['request']->getUri())->toContain('purchases/PURCHASE123/');
});

it('maps chip statuses to donation statuses correctly', function (string $chipStatus, DonationStatus $expectedStatus) {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'currency' => 'MYR',
        'gross_amount' => 100.00,
        'chip_purchase_id' => 'PURCHASE123',
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
            'status' => $chipStatus,
            'transaction_data' => [
                'payment_method' => $chipStatus === 'created' ? 'fpx' : 'visa',
            ],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    ChipApiFactory::fake(make: fn () => $chipApi);

    app(SyncDonationDetails::class)->sync($donation);

    $freshDonation = $donation->fresh();

    expect($freshDonation->status)->toBe($expectedStatus)
        ->and($freshDonation->payment_method_brand)->toBe($chipStatus === 'created' ? 'fpx' : 'visa');

    expect(ProcessingFee::where('donation_id', $donation->id)->exists())
        ->toBe($expectedStatus === DonationStatus::Succeeded);
})->with([
    'paid' => ['paid', DonationStatus::Succeeded],
    'cleared' => ['cleared', DonationStatus::Succeeded],
    'settled' => ['settled', DonationStatus::Succeeded],
    'error' => ['error', DonationStatus::Failed],
    'blocked' => ['blocked', DonationStatus::Failed],
    'expired' => ['expired', DonationStatus::Failed],
    'chargeback' => ['chargeback', DonationStatus::Failed],
    'cancelled' => ['cancelled', DonationStatus::Cancelled],
    'refunded' => ['refunded', DonationStatus::Refunded],
    'preauthorized' => ['preauthorized', DonationStatus::Pending],
    'pending_refund' => ['pending_refund', DonationStatus::Pending],
    'created' => ['created', DonationStatus::Pending],
]);

it('throws runtime exception when chip api request fails', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'currency' => 'MYR',
        'gross_amount' => 100.00,
        'chip_purchase_id' => 'PURCHASE123',
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

    ChipApiFactory::fake(make: fn () => $chipApi);

    expect(fn () => app(SyncDonationDetails::class)->sync($donation))
        ->toThrow(RuntimeException::class, 'Failed to sync CHIP donation details');
});

it('throws runtime exception when chip client cannot be initialized', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'currency' => 'MYR',
        'gross_amount' => 100.00,
        'chip_purchase_id' => 'PURCHASE123',
    ]);

    ChipApiFactory::fake(make: fn () => throw new InvalidArgumentException('Organization is not CHIP onboarded.'));

    expect(fn () => app(SyncDonationDetails::class)->sync($donation))
        ->toThrow(RuntimeException::class, 'Failed to initialize CHIP client');
});

it('calculates method-specific platform processing fee for fpx', function () {
    config([
        'services.chip.fpx_processing_fee_percent' => 1.5,
    ]);

    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'currency' => 'MYR',
        'gross_amount' => 100.00,
        'chip_purchase_id' => 'PURCHASE123',
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
            'status' => 'paid',
            'payment' => [
                'fee_amount' => 100,
            ],
            'transaction_data' => [
                'payment_method' => 'fpx',
            ],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    ChipApiFactory::fake(make: fn () => $chipApi);

    app(SyncDonationDetails::class)->sync($donation);

    $donation = $donation->fresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->chip_fee)->toBe('1.00')
        ->and($donation->processing_fee)->toBe('1.50')
        ->and($donation->net_amount)->toBe('97.50')
        ->and($donation->payment_method_brand)->toBe('fpx');

    $fee = ProcessingFee::where('donation_id', $donation->id)->firstOrFail();

    expect($fee->fee_amount)->toBe('1.50')
        ->and($fee->fee_percentage)->toBe('1.50')
        ->and($fee->status)->toBe('pending');
});
