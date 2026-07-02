<?php

use App\Livewire\App\Settings\Payment;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_123',
        'stripe_onboarded' => true,
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $this->user = User::factory()->for($this->organization)->create();
});

it('loads current enabled state for both processors', function () {
    $this->organization->update(['stripe_enabled' => false, 'chip_enabled' => false]);

    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->assertSet('stripeEnabled', false)
        ->assertSet('chipEnabled', false);
});

it('disables stripe for checkout without clearing stripe credentials', function () {
    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->set('stripeEnabled', false)
        ->call('toggleStripeEnabled');

    $this->organization->refresh();

    expect($this->organization->stripe_enabled)->toBeFalse()
        ->and($this->organization->stripe_account_id)->toBe('acct_123');
});

it('disables chip for checkout without clearing chip credentials', function () {
    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->set('chipEnabled', false)
        ->call('toggleChipEnabled');

    $this->organization->refresh();

    expect($this->organization->chip_enabled)->toBeFalse()
        ->and($this->organization->chip_api_key)->toBe('secret');
});
