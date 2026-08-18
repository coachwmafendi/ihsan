<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Livewire;
use Stripe\PaymentIntent;

it('records pending status details when confirmPayment receives a non-succeeded payment intent', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_requires_action_123',
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_requires_action_123',
        'object' => 'payment_intent',
        'status' => 'requires_action',
        'client_secret' => 'pi_requires_action_123_secret',
        'last_payment_error' => null,
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('confirmPayment', 'pi_requires_action_123', $paymentIntent);

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->stripe_fee_details['pending']['status'] ?? null)->toBe('requires_action')
        ->and($donation->status_tooltip)->toBe('Awaiting 3D Secure authentication');
});

it('marks donation as failed when confirmPayment receives a terminal failure from Stripe', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_card_declined_123',
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_card_declined_123',
        'object' => 'payment_intent',
        'status' => 'requires_payment_method',
        'client_secret' => 'pi_card_declined_123_secret',
        'last_payment_error' => [
            'message' => 'Your card was declined.',
            'decline_code' => 'generic_decline',
            'code' => 'card_declined',
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('confirmPayment', 'pi_card_declined_123', $paymentIntent);

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Failed)
        ->and($donation->stripe_fee_details['pending']['message'] ?? null)->toBe('Your card was declined.')
        ->and($donation->stripe_fee_details['pending']['decline_code'] ?? null)->toBe('generic_decline')
        ->and($donation->status_tooltip)->toBe('Your card was declined. (generic_decline)');
});
