<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Livewire\App\Donations\DonationShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use App\Support\PaymentFailureReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Stripe's reason for a decline was already stored on every failed donation and
 * shown on no screen at all, so answering "why did this fail?" meant opening a
 * shell and asking Stripe for a record we already had.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
});

function failedDonationWith(array $error): Donation
{
    return Donation::factory()->for(test()->campaign)->create([
        'status' => DonationStatus::Failed,
        'stripe_fee_details' => ['last_payment_error' => $error],
    ]);
}

it('reads the reason Stripe already gave us', function () {
    $donation = failedDonationWith([
        'message' => 'Your card does not support this type of purchase.',
        'decline_code' => 'transaction_not_allowed',
        'code' => 'card_declined',
    ]);

    $reason = PaymentFailureReason::for($donation);

    expect($reason)->not->toBeNull()
        // The decline code is the specific one; card_declined says almost nothing.
        ->and($reason->code)->toBe('transaction_not_allowed')
        ->and($reason->message)->toBe('Your card does not support this type of purchase.')
        ->and($reason->advice())->toContain('Prepaid cards');
});

it('falls back to the error code when the bank gave no decline code', function () {
    $reason = PaymentFailureReason::for(failedDonationWith([
        'message' => 'Your card has expired.',
        'decline_code' => null,
        'code' => 'expired_card',
    ]));

    expect($reason->code)->toBe('expired_card')
        ->and($reason->label())->toBe('expired card');
});

it('says nothing rather than filler when the code is one we cannot advise on', function () {
    $reason = PaymentFailureReason::for(failedDonationWith([
        'message' => 'The payment was declined.',
        'decline_code' => 'some_code_we_have_never_seen',
        'code' => 'card_declined',
    ]));

    expect($reason->advice())->toBeNull();
});

it('does not tell anyone to retry a card the bank reported stolen', function () {
    $reason = PaymentFailureReason::for(failedDonationWith([
        'message' => 'Your card was declined.',
        'decline_code' => 'stolen_card',
        'code' => 'card_declined',
    ]));

    expect($reason->worthRetrying())->toBeFalse()
        ->and($reason->advice())->toContain('speak to their bank');

    expect(PaymentFailureReason::for(failedDonationWith([
        'message' => 'Insufficient funds.',
        'decline_code' => 'insufficient_funds',
        'code' => 'card_declined',
    ]))->worthRetrying())->toBeTrue();
});

it('has nothing to say about a donation that never failed', function () {
    $donation = Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Succeeded,
        'stripe_fee_details' => [],
    ]);

    expect(PaymentFailureReason::for($donation))->toBeNull();
});

it('shows the reason and the advice on the donation page', function () {
    $donation = failedDonationWith([
        'message' => 'Your card does not support this type of purchase.',
        'decline_code' => 'transaction_not_allowed',
        'code' => 'card_declined',
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $donation])
        ->assertSee('Why it failed')
        ->assertSee('Your card does not support this type of purchase.')
        ->assertSee('Prepaid cards are often blocked')
        ->assertSee('transaction not allowed');
});

it('keeps the failure block off a donation that succeeded', function () {
    $donation = Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Succeeded,
        'stripe_fee_details' => ['last_payment_error' => ['message' => 'An earlier attempt failed.', 'code' => 'card_declined']],
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $donation])
        ->assertDontSee('Why it failed');
});

it('keeps Stripe developer wording away from donors but keeps it for the panel', function () {
    // A donor was sent this verbatim: "You can provide payment_method_data or a
    // new PaymentMethod to attempt to fulfill this PaymentIntent again."
    $reason = PaymentFailureReason::for(failedDonationWith([
        'message' => 'The provided PaymentMethod has failed authentication. You can provide payment_method_data or a new PaymentMethod to attempt to fulfill this PaymentIntent again.',
        'decline_code' => null,
        'code' => 'authentication_required',
    ]));

    expect($reason->donorMessage())->toBe('The bank asked for an extra confirmation step that was not completed.')
        // The panel keeps the raw text: an admin debugging a decline wants it.
        ->and($reason->message)->toContain('PaymentIntent');
});

it('falls back to plain words for a code it has never seen', function () {
    $withJargon = PaymentFailureReason::for(failedDonationWith([
        'message' => 'The SetupIntent could not be confirmed.',
        'decline_code' => 'brand_new_code',
        'code' => 'card_declined',
    ]));

    $withoutJargon = PaymentFailureReason::for(failedDonationWith([
        'message' => 'Your card was declined.',
        'decline_code' => 'another_new_code',
        'code' => 'card_declined',
    ]));

    expect($withJargon->donorMessage())->toBe('The bank declined the payment.')
        // Nothing wrong with the bank's own plain sentence; it passes through.
        ->and($withoutJargon->donorMessage())->toBe('Your card was declined.');
});

it('addresses the donor rather than talking about them', function () {
    $reason = PaymentFailureReason::for(failedDonationWith([
        'message' => 'Your card was declined.',
        'decline_code' => 'stolen_card',
        'code' => 'card_declined',
    ]));

    expect($reason->advice())->toContain('the donor')
        ->and($reason->donorAdvice())->not->toContain('the donor')
        ->and($reason->donorAdvice())->toContain('Your bank');
});

it('knows the authentication failure by the code Stripe actually sends', function () {
    // A real decline arrived as code payment_intent_authentication_failure with
    // no decline_code at all, so the mapping written for authentication_required
    // missed it and the donor got "The bank declined the payment." and no advice.
    $reason = PaymentFailureReason::for(failedDonationWith([
        'message' => 'The provided PaymentMethod has failed authentication. You can provide payment_method_data or a new PaymentMethod to attempt to fulfill this PaymentIntent again.',
        'decline_code' => null,
        'code' => 'payment_intent_authentication_failure',
    ]));

    expect($reason->donorMessage())->toContain('extra confirmation step')
        ->and($reason->donorAdvice())->toContain('finishing the bank')
        ->and($reason->advice())->not->toBeNull();
});
