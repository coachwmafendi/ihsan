<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Livewire\Livewire;
use Stripe\PaymentIntent;

it('dispatches a campaign donation received event when a public page payment is confirmed', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 1000.00,
    ]);

    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_event_test',
        'gross_amount' => 250.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_event_test',
        'object' => 'payment_intent',
        'payment_method' => null,
        'charges' => [
            'object' => 'list',
            'data' => [[
                'id' => 'ch_event_test',
                'object' => 'charge',
                'balance_transaction' => null,
                'payment_method_details' => null,
            ]],
        ],
    ]);

    Livewire::test(DonationForm::class, ['campaign' => $campaign, 'isPublicPage' => true])
        ->call('confirmPayment', 'pi_event_test', $paymentIntent)
        ->assertDispatched('campaign-donation-received', campaignPublicId: $campaign->public_id);

    $campaign->refresh();
    expect((float) $campaign->collected_amount)->toBe(1250.00);
});
