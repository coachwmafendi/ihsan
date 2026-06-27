<?php

use App\Enums\DonationStatus;
use App\Enums\PaymentGateway;
use App\Livewire\App\Donations\DonationShow;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => [
            'chip_brand_id' => 'brand-test-uuid',
            'chip_api_key' => 'chip-test-key',
            'chip_payment_methods' => ['card'],
        ],
    ]);

    $this->campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'payment_gateway' => PaymentGateway::Chip,
        'campaign_page_enabled' => true,
        'checkout_modal_enabled' => true,
    ]);

    $this->donor = Donor::factory()->create([
        'first_name' => 'Ahmad',
        'last_name' => 'Tester',
        'email' => 'ahmad@example.com',
    ]);
});

it('creates a pending donation and returns a chip checkout url', function () {
    Http::fake([
        'https://gate.chip-in.asia/api/v1/purchases/' => Http::response([
            'id' => 'purchase-test-uuid',
            'checkout_url' => 'https://gate.chip-in.asia/pay/purchase-test-uuid',
            'status' => 'created',
        ], 201),
    ]);

    Livewire::test(DonationForm::class, ['campaign' => $this->campaign])
        ->set('amount', 50)
        ->set('firstName', 'Ahmad')
        ->set('lastName', 'Tester')
        ->set('email', 'ahmad@example.com')
        ->set('currency', 'myr')
        ->call('submitChip')
        ->assertHasNoErrors()
        ->assertReturned('https://gate.chip-in.asia/pay/purchase-test-uuid');

    $donation = Donation::query()->where('campaign_id', $this->campaign->id)->firstOrFail();
    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->chip_purchase_id)->toBe('purchase-test-uuid')
        ->and($donation->chip_checkout_url)->toBe('https://gate.chip-in.asia/pay/purchase-test-uuid')
        ->and($donation->gross_amount)->toEqual(50);
});

it('confirms a chip donation after successful payment', function () {
    Http::fake([
        'https://gate.chip-in.asia/api/v1/purchases/*' => Http::response([
            'id' => 'purchase-test-uuid',
            'status' => 'paid',
            'payment' => [
                'fee_amount' => 125,
                'net_amount' => 4875,
            ],
        ]),
    ]);

    $this->campaign->update(['collected_amount' => 0]);

    $donation = Donation::factory()->state([
        'status' => DonationStatus::Pending->value,
    ])->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'chip_purchase_id' => 'purchase-test-uuid',
        'gross_amount' => 50,
        'base_amount' => null,
        'net_amount' => 50,
        'stripe_fee' => 0,
    ]);

    $this->actingAs(User::factory()->create(['organization_id' => $this->organization->id]));

    $this->postJson(route('chip.confirm', $donation))
        ->assertOk()
        ->assertJson(['success' => true, 'donation_id' => $donation->public_id]);

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and((float) $donation->stripe_fee)->toEqual(1.25)
        ->and((float) $donation->net_amount)->toEqual(48.75)
        ->and((float) $this->campaign->fresh()->collected_amount)->toEqual(50);
});

it('renders the chip callback view', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'chip_purchase_id' => 'purchase-test-uuid',
        'status' => DonationStatus::Pending,
    ]);

    $this->get(route('chip.callback', [
        'donation' => $donation->public_id,
        'status' => 'success',
        'return_to' => route('campaigns.public', $this->campaign),
    ]))
        ->assertOk()
        ->assertSee('Finalising your payment')
        ->assertSee($donation->public_id);
});

it('detects chip as the payment processor on the donation show page', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'chip_purchase_id' => 'purchase-test-uuid',
        'status' => DonationStatus::Succeeded,
    ]);

    Livewire::actingAs(User::factory()->create(['organization_id' => $this->organization->id]))
        ->test(DonationShow::class, ['donation' => $donation])
        ->assertSee('Payment processor')
        ->assertSee('CHIP');
});
