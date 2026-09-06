<?php

declare(strict_types=1);

use App\Enums\PaymentGateway;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

/**
 * CHIP's fee rates in DonationFeeEstimator were never measured against a real
 * settlement, and no donation has run through CHIP yet to measure them with.
 * Until they are confirmed, a campaign must not be able to move onto it: the
 * donor fee cover would be wrong from the first donation.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
        'chip_enabled' => true,
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($this->user);
});

it('refuses to create a campaign on CHIP while it is locked', function () {
    config(['services.chip.donations_enabled' => false]);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Wakaf Pembinaan')
        ->set('status', 'draft')
        ->set('payment_gateway', 'chip')
        ->call('save')
        ->assertHasErrors('payment_gateway');

    expect(Campaign::query()->count())->toBe(0);
});

it('refuses to move an existing campaign onto CHIP while it is locked', function () {
    config(['services.chip.donations_enabled' => false]);

    $campaign = Campaign::factory()->for($this->organization)->create([
        'payment_gateway' => PaymentGateway::Stripe,
    ]);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('payment_gateway', 'chip')
        ->call('save')
        ->assertHasErrors('payment_gateway');

    expect($campaign->fresh()->payment_gateway)->toBe(PaymentGateway::Stripe);
});

it('hides CHIP from the processor picker while it is locked', function () {
    config(['services.chip.donations_enabled' => false]);

    Livewire::test(CampaignCreate::class)->assertDontSee('CHIP');
});

it('leaves a campaign already on CHIP alone', function () {
    config(['services.chip.donations_enabled' => false]);

    $campaign = Campaign::factory()->for($this->organization)->create([
        'payment_gateway' => PaymentGateway::Chip,
        'title' => 'Kempen CHIP sedia ada',
    ]);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('thank_you_message', 'Terima kasih')
        ->call('save')
        ->assertHasNoErrors('payment_gateway');

    expect($campaign->fresh()->payment_gateway)->toBe(PaymentGateway::Chip);
});

it('allows CHIP once the rates are confirmed and the lock is lifted', function () {
    config(['services.chip.donations_enabled' => true]);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Wakaf Pembinaan CHIP')
        ->set('status', 'draft')
        ->set('payment_gateway', 'chip')
        ->call('save')
        ->assertHasNoErrors('payment_gateway');

    expect(Campaign::query()->first()?->payment_gateway)->toBe(PaymentGateway::Chip);
});
