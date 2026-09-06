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

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);
});

it('creates a campaign with stripe gateway by default', function () {
    $this->actingAs($this->user);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Stripe Campaign')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $campaign = Campaign::query()->latest()->firstOrFail();

    expect($campaign->payment_gateway)->toBe(PaymentGateway::Stripe);
});

it('creates a campaign with chip gateway when organization is onboarded', function () {
    // CHIP selection is gated until its fee rates are confirmed.
    config(['services.chip.donations_enabled' => true]);

    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'CHIP Campaign')
        ->set('status', 'draft')
        ->set('payment_gateway', 'chip')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $campaign = Campaign::query()->latest()->firstOrFail();

    expect($campaign->payment_gateway)->toBe(PaymentGateway::Chip);
});

it('rejects chip gateway when organization is not onboarded on create', function () {
    $this->actingAs($this->user);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'CHIP Campaign')
        ->set('status', 'draft')
        ->set('payment_gateway', 'chip')
        ->call('save')
        ->assertHasErrors(['payment_gateway'])
        ->assertNoRedirect();

    expect(Campaign::query()->where('title', 'CHIP Campaign')->exists())->toBeFalse();
});

it('updates a campaign to chip gateway when organization is onboarded', function () {
    // CHIP selection is gated until its fee rates are confirmed.
    config(['services.chip.donations_enabled' => true]);

    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->create([
        'organization_id' => $organization->id,
        'payment_gateway' => PaymentGateway::Stripe,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('payment_gateway', 'chip')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $campaign->refresh();

    expect($campaign->payment_gateway)->toBe(PaymentGateway::Chip);
});

it('rejects chip gateway when organization is not onboarded on update', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'payment_gateway' => PaymentGateway::Stripe,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('payment_gateway', 'chip')
        ->call('save')
        ->assertHasErrors(['payment_gateway']);

    $campaign->refresh();

    expect($campaign->payment_gateway)->toBe(PaymentGateway::Stripe);
});
