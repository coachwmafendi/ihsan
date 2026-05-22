<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\ProcessStripeWebhook;
use App\Jobs\SendDonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Queue;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

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

it('creates recurring subscriptions in the connected account from payment intent webhooks', function () {
    Queue::fake([SendDonationReceipt::class]);
    config(['services.stripe.secret' => 'sk_test_fake']);

    $stripeClient = new class implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>}> */
        public array $requests = [];

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'headers' => $headers,
                'params' => $params,
            ];

            $response = match (true) {
                str_contains($absUrl, '/v1/payment_methods/') => [
                    'id' => 'pm_connected_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ],
                str_ends_with($absUrl, '/v1/products') => [
                    'id' => 'prod_connected_campaign',
                    'object' => 'product',
                ],
                str_ends_with($absUrl, '/v1/prices') => [
                    'id' => 'price_connected_monthly',
                    'object' => 'price',
                ],
                str_ends_with($absUrl, '/v1/subscriptions') => [
                    'id' => 'sub_connected_monthly',
                    'object' => 'subscription',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_connected_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'email' => 'connected-recurring@example.test',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 25,
        'net_amount' => 25,
        'platform_fee' => 0,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
        'stripe_payment_intent_id' => 'pi_connected_recurring_123',
    ]);

    try {
        (new ProcessStripeWebhook(connectedRecurringPaymentIntentSucceededPayload($donation)))->handle();
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $donation->refresh();
    $subscription = Subscription::query()->where('stripe_subscription_id', 'sub_connected_monthly')->first();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->payment_method_brand)->toBe('visa')
        ->and($donation->payment_method_last4)->toBe('4242')
        ->and($subscription)->not->toBeNull()
        ->and($subscription->campaign_id)->toBe($campaign->getKey());

    $connectedRequests = collect($stripeClient->requests)
        ->filter(fn (array $request): bool => str_contains($request['url'], '/v1/payment_methods/')
            || str_ends_with($request['url'], '/v1/products')
            || str_ends_with($request['url'], '/v1/prices')
            || str_ends_with($request['url'], '/v1/subscriptions'));

    expect($connectedRequests)->toHaveCount(4)
        ->and($connectedRequests->every(fn (array $request): bool => in_array('Stripe-Account: acct_connected_test', $request['headers'], true)))->toBeTrue();

    $subscriptionRequest = collect($stripeClient->requests)
        ->first(fn (array $request): bool => str_ends_with($request['url'], '/v1/subscriptions'));

    expect($subscriptionRequest['params'])->toMatchArray([
        'customer' => 'cus_connected_donor',
        'default_payment_method' => 'pm_connected_card',
    ]);

    Queue::assertPushed(SendDonationReceipt::class);
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

function connectedRecurringPaymentIntentSucceededPayload(Donation $donation): string
{
    return json_encode([
        'id' => 'evt_connected_recurring_123',
        'object' => 'event',
        'account' => 'acct_connected_test',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $donation->stripe_payment_intent_id,
                'object' => 'payment_intent',
                'customer' => 'cus_connected_donor',
                'metadata' => [
                    'donation_id' => (string) $donation->getKey(),
                    'donor_email' => $donation->donor?->email ?? '',
                    'campaign_id' => (string) $donation->campaign_id,
                    'organization_id' => (string) $donation->campaign?->organization_id,
                ],
                'payment_method' => 'pm_connected_card',
                'charges' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'ch_connected_recurring_123',
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
