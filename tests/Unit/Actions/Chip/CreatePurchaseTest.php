<?php

use App\Actions\Chip\ChipApiFactory;
use App\Actions\Chip\CreatePurchase;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

afterEach(fn () => Mockery::close());

it('creates a chip purchase and stores checkout url', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create(['payment_gateway' => 'chip']);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'MYR',
        'gross_amount' => 10000,
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
            'checkout_url' => 'https://gate.chip-in.asia/pay/PURCHASE123',
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    Route::get('/chip/callback/{donation}/{status}', fn () => '')->name('chip.callback');
    Route::post('/chip/webhook', fn () => '')->name('chip.webhook');
    Route::getRoutes()->refreshNameLookups();

    $factory = Mockery::mock('alias:'.ChipApiFactory::class);
    $factory->shouldReceive('make')
        ->once()
        ->andReturn($chipApi);

    $checkoutUrl = app(CreatePurchase::class)->create($donation);

    expect($checkoutUrl)->toBe('https://gate.chip-in.asia/pay/PURCHASE123');
    expect($donation->fresh()->chip_purchase_id)->toBe('PURCHASE123');
    expect($donation->fresh()->chip_checkout_url)->toBe('https://gate.chip-in.asia/pay/PURCHASE123');
});
