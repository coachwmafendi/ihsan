<?php

use App\Livewire\App\Settings\Payment;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_123',
        'stripe_onboarded' => true,
    ]);
    $this->user = User::factory()->for($this->organization)->create();
});

it('shows a success modal on payment settings after stripe connect', function () {
    $this->withSession(['stripe_connect_success' => 'Stripe account connected successfully.']);

    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->assertSet('showStripeConnectedModal', true)
        ->assertSee('Stripe connected');
});

it('does not show the success modal without the stripe connect flash', function () {
    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->assertSet('showStripeConnectedModal', false)
        ->assertDontSee('Stripe connected successfully');
});

it('dismisses the success modal', function () {
    $this->withSession(['stripe_connect_success' => 'Stripe account connected successfully.']);

    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->set('showStripeConnectedModal', false)
        ->assertDontSee('Stripe connected successfully');
});
