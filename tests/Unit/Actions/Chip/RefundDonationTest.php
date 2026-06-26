<?php

use App\Actions\Chip\ChipApiFactory;
use App\Actions\Chip\RefundDonation;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

afterEach(fn () => Mockery::close());

it('refunds a chip donation and updates status', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => 'PURCHASE123',
        'status' => DonationStatus::Succeeded,
    ]);

    $requestHistory = [];
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
            'status' => 'refunded',
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

    app(RefundDonation::class)->handle($donation);

    $donation = $donation->fresh();

    expect($donation->status)->toBe(DonationStatus::Refunded)
        ->and($donation->refunded_at)->not->toBeNull();

    expect($requestHistory)->toHaveCount(1);
    expect($requestHistory[0]['request']->getMethod())->toBe('POST');
    expect((string) $requestHistory[0]['request']->getUri())->toContain('purchases/PURCHASE123/refund/');
    expect((string) $requestHistory[0]['request']->getBody())->toBe('');
});

it('throws runtime exception when chip api request fails', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => 'PURCHASE123',
        'status' => DonationStatus::Succeeded,
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

    expect(fn () => app(RefundDonation::class)->handle($donation))
        ->toThrow(RuntimeException::class, 'Failed to refund CHIP donation');
});

it('throws runtime exception when donation has no chip purchase id', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => null,
        'status' => DonationStatus::Succeeded,
    ]);

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldNotReceive('make');

    expect(fn () => app(RefundDonation::class)->handle($donation))
        ->toThrow(RuntimeException::class, 'No CHIP purchase ID found');
});

it('throws runtime exception when organization is not chip onboarded', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => 'PURCHASE123',
        'status' => DonationStatus::Succeeded,
    ]);

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')
        ->once()
        ->andThrow(new InvalidArgumentException('Organization is not CHIP onboarded.'));

    expect(fn () => app(RefundDonation::class)->handle($donation))
        ->toThrow(RuntimeException::class, 'Failed to initialize CHIP client');
});
