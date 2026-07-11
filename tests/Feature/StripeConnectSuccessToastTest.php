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

it('shows a success toast on payment settings after stripe connect', function () {
    $this->withSession(['stripe_connect_success' => 'Stripe account connected successfully.']);

    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->assertDispatched('notify', variant: 'success', message: 'Stripe account connected successfully.');
});

it('does not dispatch a toast without the stripe connect flash', function () {
    Livewire::actingAs($this->user)
        ->test(Payment::class)
        ->assertNotDispatched('notify');
});
