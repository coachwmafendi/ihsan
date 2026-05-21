<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\ProcessStripeWebhook;
use App\Jobs\SendDonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Queue;

it('rejects webhook with invalid signature', function () {
    $response = $this->postJson(route('stripe.webhook'), [
        'type' => 'payment_intent.succeeded',
    ], [
        'Stripe-Signature' => 'invalid',
    ]);

    $response->assertStatus(400);
});

it('accepts a valid stripe webhook signature and dispatches the processor job', function () {
    Queue::fake([ProcessStripeWebhook::class]);

    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

    $payload = json_encode([
        'id' => 'evt_valid_signature_123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_valid_signature_123',
                'object' => 'payment_intent',
                'metadata' => [],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test_secret');

    $response = $this->call('POST', route('stripe.webhook'), content: $payload, server: [
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
    ]);

    $response->assertOk()
        ->assertJson(['received' => true]);

    Queue::assertPushed(ProcessStripeWebhook::class, fn (ProcessStripeWebhook $job): bool => $job->payload === $payload);
});

it('marks a pending one time donation as succeeded from a payment intent webhook', function () {
    Queue::fake([SendDonationReceipt::class]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 25,
    ]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'net_amount' => 50,
        'platform_fee' => 0,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_webhook_success_123',
    ]);

    (new ProcessStripeWebhook(paymentIntentSucceededPayload($donation, 'evt_webhook_success_123')))->handle();

    $donation->refresh();
    $campaign->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->stripe_charge_id)->toBe('ch_webhook_success_123')
        ->and($donation->net_amount)->toBe('50.00')
        ->and($campaign->collected_amount)->toBe('75.00')
        ->and(WebhookLog::query()->where('stripe_event_id', 'evt_webhook_success_123')->first()?->status)->toBe('completed');

    Queue::assertPushed(SendDonationReceipt::class);
});

it('does not process the same completed payment intent webhook twice', function () {
    Queue::fake([SendDonationReceipt::class]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 25,
    ]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'net_amount' => 50,
        'platform_fee' => 0,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_webhook_duplicate_123',
    ]);
    $payload = paymentIntentSucceededPayload($donation, 'evt_webhook_duplicate_123');

    (new ProcessStripeWebhook($payload))->handle();
    (new ProcessStripeWebhook($payload))->handle();

    $donation->refresh();
    $campaign->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($campaign->collected_amount)->toBe('75.00')
        ->and(WebhookLog::query()->where('stripe_event_id', 'evt_webhook_duplicate_123')->count())->toBe(1);

    Queue::assertPushedTimes(SendDonationReceipt::class, 1);
});

function paymentIntentSucceededPayload(Donation $donation, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $donation->stripe_payment_intent_id,
                'object' => 'payment_intent',
                'metadata' => [
                    'donation_id' => (string) $donation->getKey(),
                    'donor_email' => $donation->donor?->email ?? '',
                    'campaign_id' => (string) $donation->campaign_id,
                    'organization_id' => (string) $donation->campaign?->organization_id,
                ],
                'payment_method' => null,
                'charges' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'ch_webhook_success_123',
                            'object' => 'charge',
                            'balance_transaction' => null,
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

it('rejects webhook with invalid payload', function () {
    $response = $this->call('POST', route('stripe.webhook'), content: 'not-json', server: [
        'HTTP_Stripe-Signature' => 't=123,v1=whatever',
    ]);

    $response->assertStatus(400);
});
