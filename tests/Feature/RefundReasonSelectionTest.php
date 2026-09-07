<?php

declare(strict_types=1);

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Livewire\App\Donations\DonationShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Flux teleports a modal's markup out of the component it was written in, so a
 * wire:model on a field inside one never reaches the server. The refund reason
 * was bound that way: an organiser could pick "Duplicate donation", watch it
 * appear in the select, press Refund, and be told to select a reason.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donation = Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Succeeded,
        'stripe_payment_intent_id' => 'pi_refundable',
        'stripe_charge_id' => 'ch_refundable',
    ]);
});

it('refunds with the reason the organiser picked in the modal', function () {
    $this->mock(RefundDonation::class)->shouldReceive('handle')->once();

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->call('confirmRefund', 'duplicate')
        ->assertHasNoErrors();
});

it('still refuses a refund with no reason at all', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->call('confirmRefund')
        ->assertHasErrors('refundReason');
});

it('hands the chosen reason to the action rather than binding it', function () {
    // The binding is what broke; asserting the markup keeps it from returning.
    $markup = file_get_contents(base_path('resources/views/livewire/app/donations/show.blade.php'));

    expect($markup)
        ->toContain('$wire.confirmRefund(refundReason)')
        ->not->toContain('wire:model="refundReason"');
});
