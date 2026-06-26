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

afterEach(fn () => Mockery::close());

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

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($chipApi);

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

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($chipApi);

    app(SyncDonationDetails::class)->sync($donation);

    $freshDonation = $donation->fresh();

    expect($freshDonation->status)->toBe($expectedStatus)
        ->and($freshDonation->payment_method_brand)->toBe($chipStatus === 'created' ? 'fpx' : 'visa');
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

it('falls back to purchase payment_method_details when transaction_data is absent', function () {
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
            'purchase' => [
                'payment_method_details' => [
                    'payment_method' => 'mastercard',
                ],
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
    $factory->shouldReceive('make')->once()->andReturn($chipApi);

    app(SyncDonationDetails::class)->sync($donation);

    expect($donation->fresh()->payment_method_brand)->toBe('mastercard');
});
