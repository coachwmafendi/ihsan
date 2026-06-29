<?php

use App\Actions\Chip\ChipApiFactory;
use App\Enums\DonationStatus;
use App\Livewire\App\Donations\DonationShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => ChipApiFactory::resetFake());

afterEach(fn () => ChipApiFactory::resetFake());

it('refunds a chip donation from the admin donation detail page', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => 'PURCHASE123',
        'status' => DonationStatus::Succeeded,
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
            'status' => 'refunded',
        ])),
    ]);
    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => HandlerStack::create($mockHandler)],
    );
    ChipApiFactory::fake(make: fn () => $chipApi);

    Livewire::actingAs($user)
        ->test(DonationShow::class, ['donation' => $donation->fresh()])
        ->call('openRefundModal')
        ->assertSet('showRefundModal', true)
        ->set('refundReason', 'requested_by_supporter')
        ->call('confirmRefund')
        ->assertSet('showRefundModal', false);

    $donation = $donation->fresh();

    expect($donation->status)->toBe(DonationStatus::Refunded)
        ->and($donation->refunded_at)->not->toBeNull();
});
