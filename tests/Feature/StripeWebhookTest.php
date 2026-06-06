<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\ProcessStripeWebhook;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Jobs\SyncDonationStripeDetailsJob;
use App\Mail\PlatformInvoicePaid;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\MonthlyInvoice;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\Subscription;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Mail;
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
    Queue::fake([SendDonationReceipt::class, SyncDonationStripeDetailsJob::class, SendLargeDonationNotification::class]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 25,
    ]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'net_amount' => 50,
        'processing_fee' => 0,
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
        ->and($donation->net_amount)->toBe('48.75')
        ->and($donation->processing_fee)->toBe('1.25')
        ->and($campaign->collected_amount)->toBe('75.00')
        ->and(WebhookLog::query()->where('stripe_event_id', 'evt_webhook_success_123')->first()?->status)->toBe('completed');

    Queue::assertPushed(SendDonationReceipt::class);
    Queue::assertPushed(SyncDonationStripeDetailsJob::class);
});

it('does not process the same completed payment intent webhook twice', function () {
    Queue::fake([SendDonationReceipt::class, SyncDonationStripeDetailsJob::class, SendLargeDonationNotification::class]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 25,
    ]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'net_amount' => 50,
        'processing_fee' => 0,
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
    Queue::assertPushedTimes(SyncDonationStripeDetailsJob::class, 1);
});

it('creates recurring subscriptions in the connected account from payment intent webhooks', function () {
    Queue::fake([SendDonationReceipt::class, SyncDonationStripeDetailsJob::class, SendNewSubscriptionNotification::class, SendLargeDonationNotification::class]);
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
                str_contains($absUrl, '/v1/balance_transactions/txn_connected_recurring_123') => [
                    'id' => 'txn_connected_recurring_123',
                    'object' => 'balance_transaction',
                    'fee' => 275,
                    'fee_details' => [
                        [
                            'amount' => 150,
                            'currency' => 'myr',
                            'type' => 'stripe_fee',
                        ],
                        [
                            'amount' => 125,
                            'currency' => 'myr',
                            'type' => 'application_fee',
                        ],
                    ],
                ],
                str_contains($absUrl, '/v1/payment_intents/pi_connected_recurring_123') => [
                    'id' => 'pi_connected_recurring_123',
                    'object' => 'payment_intent',
                    'customer' => 'cus_connected_donor',
                    'payment_method' => 'pm_connected_card',
                    'metadata' => [
                        'donor_email' => 'connected-recurring@example.test',
                    ],
                    'latest_charge' => [
                        'id' => 'ch_connected_recurring_123',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'txn_connected_recurring_123',
                            'object' => 'balance_transaction',
                            'fee' => 275,
                            'fee_details' => [
                                [
                                    'amount' => 150,
                                    'currency' => 'myr',
                                    'type' => 'stripe_fee',
                                ],
                                [
                                    'amount' => 125,
                                    'currency' => 'myr',
                                    'type' => 'application_fee',
                                ],
                            ],
                        ],
                    ],
                    'charges' => [
                        'object' => 'list',
                        'data' => [],
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
        'processing_fee' => 0,
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
        ->and($donation->payment_method_type)->toBe('card')
        ->and($donation->stripe_fee)->toBe('1.50')
        ->and($donation->processing_fee)->toBe('1.25')
        ->and($donation->net_amount)->toBe('22.25')
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
    Queue::assertPushed(SyncDonationStripeDetailsJob::class);
});

it('syncs stripe details for an already succeeded connected donation without duplicating fulfillment', function () {
    Queue::fake([SendDonationReceipt::class, SyncDonationStripeDetailsJob::class]);
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
                    'id' => 'pm_synced_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ],
                str_contains($absUrl, '/v1/balance_transactions/txn_synced_fee_123') => [
                    'id' => 'txn_synced_fee_123',
                    'object' => 'balance_transaction',
                    'fee' => 1909,
                    'fee_details' => [
                        [
                            'amount' => 904,
                            'currency' => 'myr',
                            'type' => 'stripe_fee',
                        ],
                        [
                            'amount' => 1005,
                            'currency' => 'myr',
                            'type' => 'application_fee',
                        ],
                    ],
                ],
                str_contains($absUrl, '/v1/payment_intents/pi_already_succeeded_123') => [
                    'id' => 'pi_already_succeeded_123',
                    'object' => 'payment_intent',
                    'customer' => 'cus_synced_donor',
                    'payment_method' => 'pm_synced_card',
                    'metadata' => [
                        'donation_id' => 'already-succeeded-donation',
                    ],
                    'latest_charge' => [
                        'id' => 'ch_synced_fee_123',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'txn_synced_fee_123',
                            'object' => 'balance_transaction',
                            'fee' => 1909,
                        ],
                    ],
                    'charges' => [
                        'object' => 'list',
                        'data' => [],
                    ],
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
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 201,
    ]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 201,
        'stripe_fee' => 0,
        'processing_fee' => 0,
        'net_amount' => 201,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_already_succeeded_123',
    ]);

    try {
        (new ProcessStripeWebhook(connectedPaymentIntentSucceededPayload($donation, 'evt_synced_fee_123')))->handle();
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $donation->refresh();
    $campaign->refresh();

    expect($donation->stripe_charge_id)->toBe('ch_synced_fee_123')
        ->and($donation->payment_method_brand)->toBe('visa')
        ->and($donation->payment_method_type)->toBe('card')
        ->and($donation->stripe_fee)->toBe('9.04')
        ->and($donation->processing_fee)->toBe('10.05')
        ->and($donation->net_amount)->toBe('181.91')
        ->and($campaign->collected_amount)->toBe('201.00');

    Queue::assertNotPushed(SendDonationReceipt::class);
    Queue::assertPushed(SyncDonationStripeDetailsJob::class);
});

it('marks platform fee invoices as paid from stripe webhook', function () {
    Mail::fake();

    $organization = Organization::factory()->create(['contact_email' => 'ngo@example.com']);
    $invoice = MonthlyInvoice::factory()->create([
        'organization_id' => $organization->id,
        'stripe_invoice_id' => 'in_platform_test',
        'stripe_status' => 'open',
        'total_fees' => 100.00,
    ]);
    $fee1 = ProcessingFee::factory()->create([
        'organization_id' => $organization->id,
        'fee_amount' => 50.00,
        'status' => 'invoiced',
        'monthly_invoice_id' => $invoice->id,
    ]);
    $fee2 = ProcessingFee::factory()->create([
        'organization_id' => $organization->id,
        'fee_amount' => 50.00,
        'status' => 'invoiced',
        'monthly_invoice_id' => $invoice->id,
    ]);

    $payload = json_encode([
        'id' => 'evt_platform_invoice_paid',
        'object' => 'event',
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_platform_test',
                'object' => 'invoice',
                'status' => 'paid',
                'amount_paid' => 10000,
                'subscription' => null,
                'metadata' => [
                    'organization_id' => (string) $organization->id,
                    'period' => now()->subMonth()->format('Y-m-d'),
                    'type' => 'processing_fees',
                ],
                'created' => now()->timestamp,
                'period_start' => now()->subMonth()->timestamp,
                'period_end' => now()->timestamp,
                'payment_intent' => null,
                'charge' => null,
                'currency' => 'myr',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    (new ProcessStripeWebhook($payload))->handle();

    $invoice->refresh();
    $fee1->refresh();
    $fee2->refresh();

    expect($invoice->stripe_status)->toBe('paid')
        ->and($invoice->paid_at)->not->toBeNull()
        ->and($fee1->status)->toBe('paid')
        ->and($fee2->status)->toBe('paid');

    Mail::assertQueued(PlatformInvoicePaid::class, fn (PlatformInvoicePaid $mailable) => $mailable->invoice->id === $invoice->id);
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

function connectedPaymentIntentSucceededPayload(Donation $donation, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'account' => 'acct_connected_test',
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
