<?php

use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\ProcessStripeWebhook;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendDonorNewSubscriptionNotification;
use App\Jobs\SendDonorRecurringPaymentNotification;
use App\Jobs\SendFailedPaymentNotification;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Jobs\SyncDonationStripeDetailsJob;
use App\Mail\PlatformInvoicePaid;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\MonthlyInvoice;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\Subscription;
use App\Models\TrackingConfiguration;
use App\Models\WebhookLog;
use App\Services\RecurringPlanResolver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\PaymentIntent as StripePaymentIntent;

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

it('stores stripe failure reason when a one time payment intent fails', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_webhook_failed_123',
    ]);

    (new ProcessStripeWebhook(paymentIntentFailedPayload($donation, 'evt_webhook_failed_123')))->handle();

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Failed)
        ->and($donation->stripe_fee_details['last_payment_error']['message'] ?? null)->toBe('Your card has insufficient funds.')
        ->and($donation->stripe_fee_details['last_payment_error']['decline_code'] ?? null)->toBe('insufficient_funds')
        ->and($donation->status_tooltip)->toBe('Your card has insufficient funds. The bank returned the decline code insufficient_funds.')
        ->and(WebhookLog::query()->where('stripe_event_id', 'evt_webhook_failed_123')->first()?->status)->toBe('completed');
});

it('stores pending status when a payment intent is created', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_webhook_created_123',
    ]);

    (new ProcessStripeWebhook(paymentIntentCreatedPayload($donation, 'evt_webhook_created_123')))->handle();

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->stripe_fee_details['pending']['status'] ?? null)->toBe('requires_payment_method')
        ->and($donation->status_tooltip)->toBe('Payment method not provided (requires_payment_method)');
});

it('stores requires_action status when a payment intent requires action', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_webhook_requires_action_123',
    ]);

    (new ProcessStripeWebhook(paymentIntentRequiresActionPayload($donation, 'evt_webhook_requires_action_123')))->handle();

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->stripe_fee_details['pending']['status'] ?? null)->toBe('requires_action')
        ->and($donation->status_tooltip)->toBe('Awaiting 3D Secure authentication (requires_action)');
});

it('marks donation as failed when a payment intent is canceled', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_webhook_canceled_123',
    ]);

    (new ProcessStripeWebhook(paymentIntentCanceledPayload($donation, 'evt_webhook_canceled_123')))->handle();

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Failed)
        ->and($donation->stripe_fee_details['pending']['status'] ?? null)->toBe('canceled')
        ->and($donation->stripe_fee_details['pending']['cancellation_reason'] ?? null)->toBe('abandoned')
        ->and($donation->status_tooltip)->toBe('Payment canceled: abandoned');
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

it('creates app-controlled recurring subscriptions from payment intent webhooks', function () {
    Queue::fake([SendDonationReceipt::class, SyncDonationStripeDetailsJob::class, SendNewSubscriptionNotification::class, SendDonorNewSubscriptionNotification::class, SendLargeDonationNotification::class]);
    config(['services.stripe.secret' => 'sk_test_fake']);
    config(['services.recurring.use_app_controlled' => true]);

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
                str_contains($absUrl, '/v1/payment_methods/pm_app_controlled_card') => [
                    'id' => 'pm_app_controlled_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                        'country' => 'MY',
                        'exp_month' => 12,
                        'exp_year' => 2030,
                    ],
                ],
                str_contains($absUrl, '/v1/charges/ch_app_controlled_123') => [
                    'id' => 'ch_app_controlled_123',
                    'object' => 'charge',
                    'balance_transaction' => [
                        'id' => 'txn_app_controlled_123',
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
                str_contains($absUrl, '/v1/payment_intents/pi_app_controlled_webhook_123') => [
                    'id' => 'pi_app_controlled_webhook_123',
                    'object' => 'payment_intent',
                    'customer' => 'cus_app_controlled_donor',
                    'payment_method' => 'pm_app_controlled_card',
                ],
                str_ends_with($absUrl, '/v1/customers') => [
                    'id' => 'cus_app_controlled_donor',
                    'object' => 'customer',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donor = Donor::factory()->create([
        'email' => 'app-controlled-webhook@example.test',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'net_amount' => 50,
        'processing_fee' => 0,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
        'stripe_payment_intent_id' => 'pi_app_controlled_webhook_123',
    ]);

    $payload = json_encode([
        'id' => 'evt_app_controlled_webhook_123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_app_controlled_webhook_123',
                'object' => 'payment_intent',
                'customer' => null,
                'metadata' => [
                    'donation_id' => (string) $donation->getKey(),
                    'donor_email' => $donation->donor?->email ?? '',
                    'campaign_id' => (string) $donation->campaign_id,
                    'organization_id' => (string) $donation->campaign?->organization_id,
                ],
                'payment_method' => 'pm_app_controlled_card',
                'charges' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'ch_app_controlled_123',
                            'object' => 'charge',
                            'balance_transaction' => null,
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    try {
        (new ProcessStripeWebhook($payload))->handle();
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $donation->refresh();
    $subscription = $donation->subscription;

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($subscription)->not->toBeNull()
        ->and($subscription->stripe_subscription_id)->toBeNull()
        ->and($subscription->next_charge_at)->not->toBeNull()
        ->and($subscription->status->value)->toBe('active')
        ->and((float) $subscription->amount)->toBe(50.00);

    Queue::assertPushed(SendDonorNewSubscriptionNotification::class);
    Queue::assertNotPushed(SendDonationReceipt::class);
});

it('creates recurring subscriptions in the connected account from payment intent webhooks', function () {
    Queue::fake([SendDonationReceipt::class, SyncDonationStripeDetailsJob::class, SendNewSubscriptionNotification::class, SendDonorNewSubscriptionNotification::class, SendLargeDonationNotification::class]);
    config(['services.stripe.secret' => 'sk_test_fake']);
    config(['services.recurring.use_app_controlled' => false]);

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
                str_contains($absUrl, '/v1/subscriptions/') => [
                    'id' => 'sub_connected_monthly',
                    'object' => 'subscription',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'latest_invoice' => [
                        'id' => 'in_connected_monthly_first',
                        'object' => 'invoice',
                    ],
                ],
                str_ends_with($absUrl, '/v1/subscriptions') => [
                    'id' => 'sub_connected_monthly',
                    'object' => 'subscription',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'latest_invoice' => [
                        'id' => 'in_connected_monthly_first',
                        'object' => 'invoice',
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
        ->and($donation->stripe_invoice_id)->toBe('in_connected_monthly_first')
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

    Queue::assertNotPushed(SendDonationReceipt::class);
    Queue::assertPushed(SyncDonationStripeDetailsJob::class);
    Queue::assertPushed(SendDonorNewSubscriptionNotification::class);
});

it('links the first subscription invoice to the signup donation and ignores duplicate invoice paid webhooks', function () {
    Queue::fake([
        SendCampaignMilestoneNotification::class,
        SendDonationReceipt::class,
        SendDonorNewSubscriptionNotification::class,
        SendDonorRecurringPaymentNotification::class,
        SendLargeDonationNotification::class,
        SendNewDonationNotification::class,
        SendNewSubscriptionNotification::class,
        SyncDonationStripeDetailsJob::class,
    ]);
    config(['services.stripe.secret' => 'sk_test_fake']);
    config(['services.recurring.use_app_controlled' => false]);

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
                    'id' => 'pm_duplicate_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ],
                str_contains($absUrl, '/v1/balance_transactions/txn_duplicate_recurring_123') => [
                    'id' => 'txn_duplicate_recurring_123',
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
                str_contains($absUrl, '/v1/payment_intents/pi_duplicate_recurring_123') => [
                    'id' => 'pi_duplicate_recurring_123',
                    'object' => 'payment_intent',
                    'customer' => 'cus_duplicate_donor',
                    'payment_method' => 'pm_duplicate_card',
                    'metadata' => [
                        'donor_email' => 'duplicate-invoice@example.test',
                    ],
                    'latest_charge' => [
                        'id' => 'ch_duplicate_recurring_123',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'txn_duplicate_recurring_123',
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
                    'id' => 'prod_duplicate_campaign',
                    'object' => 'product',
                ],
                str_ends_with($absUrl, '/v1/prices') => [
                    'id' => 'price_duplicate_monthly',
                    'object' => 'price',
                ],
                str_contains($absUrl, '/v1/subscriptions/') => [
                    'id' => 'sub_duplicate_monthly',
                    'object' => 'subscription',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'latest_invoice' => [
                        'id' => 'in_signup_invoice_first',
                        'object' => 'invoice',
                    ],
                ],
                str_ends_with($absUrl, '/v1/subscriptions') => [
                    'id' => 'sub_duplicate_monthly',
                    'object' => 'subscription',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'latest_invoice' => [
                        'id' => 'in_signup_invoice_first',
                        'object' => 'invoice',
                    ],
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_duplicate_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donor = Donor::factory()->create([
        'email' => 'duplicate-invoice@example.test',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 25,
        'net_amount' => 25,
        'processing_fee' => 0,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
        'stripe_payment_intent_id' => 'pi_duplicate_recurring_123',
    ]);

    $payload = json_encode([
        'id' => 'evt_duplicate_recurring_123',
        'object' => 'event',
        'account' => 'acct_duplicate_test',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $donation->stripe_payment_intent_id,
                'object' => 'payment_intent',
                'customer' => 'cus_duplicate_donor',
                'metadata' => [
                    'donation_id' => (string) $donation->getKey(),
                    'donor_email' => $donation->donor?->email ?? '',
                    'campaign_id' => (string) $donation->campaign_id,
                    'organization_id' => (string) $donation->campaign?->organization_id,
                ],
                'payment_method' => 'pm_duplicate_card',
                'charges' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'ch_duplicate_recurring_123',
                            'object' => 'charge',
                            'balance_transaction' => null,
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    try {
        (new ProcessStripeWebhook($payload))->handle();
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $donation->refresh();

    expect($donation->stripe_invoice_id)->toBe('in_signup_invoice_first');

    Queue::assertPushed(SendDonorNewSubscriptionNotification::class);
    Queue::assertPushed(SendNewSubscriptionNotification::class);
    Queue::assertNotPushed(SendDonationReceipt::class);

    // Simulate Stripe sending an invoice.paid webhook for the same first invoice.
    // Because the donation already has this invoice id, no duplicate donation or
    // receipt should be created — only the original welcome email flow remains.
    $invoicePayload = donorInvoicePaidPayload(
        subscriptionId: 'sub_duplicate_monthly',
        eventId: 'evt_invoice_paid_duplicate',
        invoiceId: 'in_signup_invoice_first',
        amountPaid: 2500,
        paymentIntentId: 'pi_duplicate_invoice_123',
        chargeId: 'ch_duplicate_invoice_123',
    );

    (new ProcessStripeWebhook($invoicePayload))->handle();

    expect(Donation::query()->where('stripe_invoice_id', 'in_signup_invoice_first')->count())->toBe(1)
        ->and(Donation::query()->count())->toBe(1);

    Queue::assertPushed(SendDonorNewSubscriptionNotification::class, 1);
    Queue::assertPushed(SendNewSubscriptionNotification::class, 1);
    Queue::assertNotPushed(SendDonationReceipt::class);
    Queue::assertNotPushed(SendDonorRecurringPaymentNotification::class);
    Queue::assertNotPushed(SendNewDonationNotification::class);
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

it('ignores zero amount subscription invoices without sending donation notifications', function () {
    Queue::fake([
        SendCampaignMilestoneNotification::class,
        SendDonationReceipt::class,
        SendLargeDonationNotification::class,
        SendNewDonationNotification::class,
        SendDonorRecurringPaymentNotification::class,
        SyncDonationStripeDetailsJob::class,
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donor = Donor::factory()->create([
        'email' => 'ashrafhakim@google.sg',
    ]);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => 'sub_trial_invoice',
        'payment_count' => 1,
    ]);

    (new ProcessStripeWebhook(donorInvoicePaidPayload(
        subscriptionId: 'sub_trial_invoice',
        eventId: 'evt_trial_invoice_paid',
        invoiceId: 'in_trial_invoice',
        amountPaid: 0,
        paymentIntentId: null,
    )))->handle();

    $subscription->refresh();
    $campaign->refresh();

    expect(Donation::query()->where('subscription_id', $subscription->getKey())->count())->toBe(0)
        ->and($subscription->payment_count)->toBe(1)
        ->and($campaign->collected_amount)->toBe('0.00')
        ->and(WebhookLog::query()->where('stripe_event_id', 'evt_trial_invoice_paid')->first()?->status)->toBe('completed');

    Queue::assertNotPushed(SendNewDonationNotification::class);
    Queue::assertNotPushed(SendDonationReceipt::class);
    Queue::assertNotPushed(SendDonorRecurringPaymentNotification::class);
});

it('does not duplicate recurring donation notifications when invoice paid webhooks are retried', function () {
    Queue::fake([
        SendCampaignMilestoneNotification::class,
        SendDonationReceipt::class,
        SendLargeDonationNotification::class,
        SendNewDonationNotification::class,
        SendDonorRecurringPaymentNotification::class,
        SyncDonationStripeDetailsJob::class,
    ]);

    $this->mock(SyncDonationStripeDetails::class, function ($mock): void {
        $mock->shouldReceive('sync')
            ->once()
            ->andReturn([
                'payment_intent' => StripePaymentIntent::constructFrom([
                    'id' => 'pi_recurring_invoice',
                    'object' => 'payment_intent',
                ]),
                'charge_id' => 'ch_recurring_invoice',
            ]);
    });

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donor = Donor::factory()->create([
        'email' => 'ashrafhakim@google.sg',
    ]);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => 'sub_paid_invoice',
        'payment_count' => 1,
    ]);
    $payload = donorInvoicePaidPayload(
        subscriptionId: 'sub_paid_invoice',
        eventId: 'evt_paid_invoice_retry',
        invoiceId: 'in_paid_invoice',
        amountPaid: 3300,
        paymentIntentId: 'pi_recurring_invoice',
        chargeId: 'ch_recurring_invoice',
    );

    (new ProcessStripeWebhook($payload))->handle();

    WebhookLog::query()
        ->where('stripe_event_id', 'evt_paid_invoice_retry')
        ->update([
            'status' => 'processing',
            'processed_at' => null,
        ]);

    (new ProcessStripeWebhook($payload))->handle();

    $subscription->refresh();
    $campaign->refresh();

    expect(Donation::query()->where('stripe_invoice_id', 'in_paid_invoice')->count())->toBe(1)
        ->and($subscription->payment_count)->toBe(2)
        ->and($campaign->collected_amount)->toBe('33.00')
        ->and(WebhookLog::query()->where('stripe_event_id', 'evt_paid_invoice_retry')->first()?->status)->toBe('completed');

    Queue::assertPushedTimes(SendNewDonationNotification::class, 1);
    Queue::assertNotPushed(SendDonationReceipt::class);
    Queue::assertPushedTimes(SendDonorRecurringPaymentNotification::class, 1);
});

it('stores recurring fee cover details on subscription creation', function () {
    Queue::fake([SendDonationReceipt::class, SyncDonationStripeDetailsJob::class, SendNewSubscriptionNotification::class, SendDonorNewSubscriptionNotification::class, SendLargeDonationNotification::class]);
    config(['services.stripe.secret' => 'sk_test_fake']);
    config(['services.recurring.use_app_controlled' => false]);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_fee_cover_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'email' => 'fee-cover@example.test',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100,
        'net_amount' => 100,
        'processing_fee' => 0,
        'donor_fee_covered' => 5,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
        'stripe_payment_intent_id' => 'pi_fee_cover_recurring_123',
    ]);

    $stripeClient = new class((string) $donation->getKey()) implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>}> */
        public array $requests = [];

        public function __construct(public string $donationId) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'headers' => $headers,
                'params' => $params,
            ];

            $response = match (true) {
                str_contains($absUrl, '/v1/payment_intents/pi_fee_cover_recurring_123') => [
                    'id' => 'pi_fee_cover_recurring_123',
                    'object' => 'payment_intent',
                    'customer' => 'cus_fee_cover_donor',
                    'payment_method' => 'pm_fee_cover_card',
                    'metadata' => [
                        'donation_id' => $this->donationId,
                        'donor_email' => 'fee-cover@example.test',
                    ],
                    'latest_charge' => [
                        'id' => 'ch_fee_cover_recurring_123',
                        'object' => 'charge',
                        'balance_transaction' => null,
                    ],
                    'charges' => [
                        'object' => 'list',
                        'data' => [],
                    ],
                ],
                str_contains($absUrl, '/v1/payment_methods/') => [
                    'id' => 'pm_fee_cover_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ],
                str_ends_with($absUrl, '/v1/products') => [
                    'id' => 'prod_fee_cover_'.count($this->requests),
                    'object' => 'product',
                ],
                str_ends_with($absUrl, '/v1/prices') => [
                    'id' => 'price_fee_cover_'.count($this->requests),
                    'object' => 'price',
                ],
                str_contains($absUrl, '/v1/subscriptions/') => [
                    'id' => 'sub_fee_cover_monthly',
                    'object' => 'subscription',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'latest_invoice' => [
                        'id' => 'in_fee_cover_first',
                        'object' => 'invoice',
                    ],
                ],
                str_ends_with($absUrl, '/v1/subscriptions') => [
                    'id' => 'sub_fee_cover_monthly',
                    'object' => 'subscription',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'latest_invoice' => [
                        'id' => 'in_fee_cover_first',
                        'object' => 'invoice',
                    ],
                ],
                str_contains($absUrl, '/v1/customers') => $method === 'GET' ? [
                    'object' => 'list',
                    'data' => [],
                    'has_more' => false,
                ] : [
                    'id' => 'cus_fee_cover_donor',
                    'object' => 'customer',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    try {
        (new ProcessStripeWebhook(feeCoverRecurringPayload($donation)))->handle();
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $subscription = Subscription::query()->where('stripe_subscription_id', 'sub_fee_cover_monthly')->first();

    $donation->refresh();

    expect($subscription)->not->toBeNull()
        ->and($subscription->cover_fee)->toBeTrue()
        ->and($subscription->fee_cover_amount)->toBe(5)
        ->and($donation->stripe_invoice_id)->toBe('in_fee_cover_first');

    $subscriptionRequest = collect($stripeClient->requests)
        ->first(fn (array $request): bool => str_ends_with($request['url'], '/v1/subscriptions'));

    expect($subscriptionRequest['params']['items'])->toHaveCount(2);

    Queue::assertPushed(SendDonorNewSubscriptionNotification::class);
});

it('splits fee cover from recurring invoice paid webhooks', function () {
    Queue::fake([
        SendCampaignMilestoneNotification::class,
        SendDonationReceipt::class,
        SendLargeDonationNotification::class,
        SendNewDonationNotification::class,
        SendDonorRecurringPaymentNotification::class,
        SyncDonationStripeDetailsJob::class,
    ]);

    $this->mock(SyncDonationStripeDetails::class, function ($mock): void {
        $mock->shouldReceive('sync')
            ->once()
            ->andReturn([
                'payment_intent' => StripePaymentIntent::constructFrom([
                    'id' => 'pi_fee_cover_invoice',
                    'object' => 'payment_intent',
                ]),
                'charge_id' => 'ch_fee_cover_invoice',
            ]);
    });

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => 'sub_fee_cover_invoice',
        'amount' => 50,
        'currency' => 'myr',
        'cover_fee' => true,
        'fee_cover_amount' => 3,
        'payment_count' => 1,
    ]);

    (new ProcessStripeWebhook(donorInvoicePaidPayload(
        subscriptionId: 'sub_fee_cover_invoice',
        eventId: 'evt_fee_cover_invoice_paid',
        invoiceId: 'in_fee_cover_invoice',
        amountPaid: 5300,
        paymentIntentId: 'pi_fee_cover_invoice',
        chargeId: 'ch_fee_cover_invoice',
    )))->handle();

    $subscription->refresh();
    $campaign->refresh();
    $donation = Donation::query()->where('stripe_invoice_id', 'in_fee_cover_invoice')->first();

    expect($donation)->not->toBeNull()
        ->and($donation->gross_amount)->toBe('50.00')
        ->and($donation->donor_fee_covered)->toBe('3.00')
        ->and($subscription->payment_count)->toBe(2)
        ->and($campaign->collected_amount)->toBe('50.00')
        ->and(WebhookLog::query()->where('stripe_event_id', 'evt_fee_cover_invoice_paid')->first()?->status)->toBe('completed');

    Queue::assertNotPushed(SendDonationReceipt::class);
    Queue::assertPushedTimes(SendNewDonationNotification::class, 1);
    Queue::assertPushedTimes(SendDonorRecurringPaymentNotification::class, 1);
});

it('dispatches conversion events for payment intent succeeded webhook', function () {
    Queue::fake([
        SendMetaConversionEvent::class,
        SendLinkedInConversionEvent::class,
        SendXAdsConversionEvent::class,
        SendSnapchatConversionEvent::class,
        SendDonationReceipt::class,
        SendNewDonationNotification::class,
        SendLargeDonationNotification::class,
        SyncDonationStripeDetailsJob::class,
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'net_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_meta_webhook_123',
    ]);

    TrackingConfiguration::factory()->for($organization)->meta()->create();

    (new ProcessStripeWebhook(paymentIntentSucceededPayload($donation, 'evt_meta_webhook_123')))->handle();

    Queue::assertPushed(SendMetaConversionEvent::class, fn (SendMetaConversionEvent $job) => $job->donation->is($donation));
    Queue::assertPushed(SendLinkedInConversionEvent::class, fn ($job) => $job->donation->is($donation));
    Queue::assertPushed(SendXAdsConversionEvent::class, fn ($job) => $job->donation->is($donation));
    Queue::assertPushed(SendSnapchatConversionEvent::class, fn ($job) => $job->donation->is($donation));
});

it('dispatches conversion events when a first monthly payment creates a subscription', function () {
    Queue::fake([
        SendMetaConversionEvent::class,
        SendLinkedInConversionEvent::class,
        SendXAdsConversionEvent::class,
        SendSnapchatConversionEvent::class,
        SendDonationReceipt::class,
        SendNewDonationNotification::class,
        SendLargeDonationNotification::class,
        SyncDonationStripeDetailsJob::class,
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'net_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
        'subscription_id' => null,
        'stripe_payment_intent_id' => 'pi_meta_new_sub_123',
    ]);

    TrackingConfiguration::factory()->for($organization)->meta()->create();

    $this->mock(RecurringPlanResolver::class, function ($mock) use ($campaign, $donor): void {
        $mock->shouldReceive('create')->andReturn(
            Subscription::factory()->for($campaign)->for($donor)->create()
        );
    });

    (new ProcessStripeWebhook(paymentIntentSucceededPayload($donation, 'evt_meta_new_sub_123')))->handle();

    Queue::assertPushed(SendMetaConversionEvent::class, fn (SendMetaConversionEvent $job) => $job->donation->is($donation));
    Queue::assertPushed(SendSnapchatConversionEvent::class, fn ($job) => $job->donation->is($donation));
});

it('dispatches conversion events for recurring invoice paid webhook', function () {
    Queue::fake([
        SendMetaConversionEvent::class,
        SendLinkedInConversionEvent::class,
        SendXAdsConversionEvent::class,
        SendSnapchatConversionEvent::class,
        SendDonationReceipt::class,
        SendNewDonationNotification::class,
        SendDonorRecurringPaymentNotification::class,
        SendLargeDonationNotification::class,
        SyncDonationStripeDetailsJob::class,
    ]);

    $this->mock(SyncDonationStripeDetails::class, function ($mock): void {
        $mock->shouldReceive('sync')
            ->once()
            ->andReturn([
                'payment_intent' => StripePaymentIntent::constructFrom([
                    'id' => 'pi_meta_invoice',
                    'object' => 'payment_intent',
                ]),
                'charge_id' => 'ch_meta_invoice',
            ]);
    });

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => 'sub_meta_invoice',
        'amount' => 30,
        'fee_cover_amount' => 0,
        'payment_count' => 1,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta()->create();

    (new ProcessStripeWebhook(donorInvoicePaidPayload(
        subscriptionId: 'sub_meta_invoice',
        eventId: 'evt_meta_invoice_paid',
        invoiceId: 'in_meta_invoice',
        amountPaid: 3000,
        paymentIntentId: 'pi_meta_invoice',
        chargeId: 'ch_meta_invoice',
    )))->handle();

    $donation = Donation::query()->where('stripe_invoice_id', 'in_meta_invoice')->first();

    expect($donation)->not->toBeNull();

    Queue::assertPushed(SendMetaConversionEvent::class, fn (SendMetaConversionEvent $job) => $job->donation->is($donation));
    Queue::assertPushed(SendLinkedInConversionEvent::class, fn ($job) => $job->donation->is($donation));
    Queue::assertPushed(SendXAdsConversionEvent::class, fn ($job) => $job->donation->is($donation));
    Queue::assertPushed(SendSnapchatConversionEvent::class, fn ($job) => $job->donation->is($donation));
});

function paymentIntentCreatedPayload(Donation $donation, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'payment_intent.created',
        'data' => [
            'object' => [
                'id' => $donation->stripe_payment_intent_id,
                'object' => 'payment_intent',
                'status' => 'requires_payment_method',
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

function paymentIntentRequiresActionPayload(Donation $donation, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'payment_intent.requires_action',
        'data' => [
            'object' => [
                'id' => $donation->stripe_payment_intent_id,
                'object' => 'payment_intent',
                'status' => 'requires_action',
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

function paymentIntentCanceledPayload(Donation $donation, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'payment_intent.canceled',
        'data' => [
            'object' => [
                'id' => $donation->stripe_payment_intent_id,
                'object' => 'payment_intent',
                'status' => 'canceled',
                'cancellation_reason' => 'abandoned',
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

function paymentIntentFailedPayload(Donation $donation, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'payment_intent.payment_failed',
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
                'last_payment_error' => [
                    'message' => 'Your card has insufficient funds.',
                    'decline_code' => 'insufficient_funds',
                    'code' => 'card_declined',
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

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

function donorInvoicePaidPayload(
    string $subscriptionId,
    string $eventId,
    string $invoiceId,
    int $amountPaid,
    ?string $paymentIntentId,
    ?string $chargeId = null,
): string {
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => $invoiceId,
                'object' => 'invoice',
                'status' => 'paid',
                'amount_paid' => $amountPaid,
                'subscription' => $subscriptionId,
                'metadata' => [],
                'created' => now()->timestamp,
                'period_start' => now()->timestamp,
                'period_end' => now()->addMonth()->timestamp,
                'payment_intent' => $paymentIntentId,
                'charge' => $chargeId,
                'currency' => 'sgd',
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

function feeCoverRecurringPayload(Donation $donation): string
{
    return json_encode([
        'id' => 'evt_fee_cover_recurring_123',
        'object' => 'event',
        'account' => 'acct_fee_cover_test',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $donation->stripe_payment_intent_id,
                'object' => 'payment_intent',
                'customer' => 'cus_fee_cover_donor',
                'payment_method' => 'pm_fee_cover_card',
                'metadata' => [
                    'donation_id' => (string) $donation->getKey(),
                    'donor_email' => $donation->donor?->email ?? '',
                    'campaign_id' => (string) $donation->campaign_id,
                    'organization_id' => (string) $donation->campaign?->organization_id,
                ],
                'charges' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'ch_fee_cover_recurring_123',
                            'object' => 'charge',
                            'balance_transaction' => null,
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

it('sends failed payment notification to org admins on every retry', function () {
    Queue::fake([SendFailedPaymentNotification::class]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => 'sub_retry_scheduled',
        'status' => 'active',
        'retry_count' => 0,
    ]);

    $payload = invoicePaymentFailedPayload('sub_retry_scheduled', 3, now()->addHours(12)->timestamp, 'evt_retry_scheduled');

    (new ProcessStripeWebhook($payload))->handle();

    $subscription->refresh();

    expect($subscription->status->value)->toBe('past_due')
        ->and($subscription->retry_count)->toBe(3);

    Queue::assertPushed(SendFailedPaymentNotification::class, 1);
});

it('sends failed payment notification when no more retries are scheduled', function () {
    Queue::fake([SendFailedPaymentNotification::class]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => 'sub_final_retry',
        'status' => 'active',
        'retry_count' => 0,
    ]);

    $payload = invoicePaymentFailedPayload('sub_final_retry', 8, null, 'evt_final_retry');

    (new ProcessStripeWebhook($payload))->handle();

    $subscription->refresh();

    expect($subscription->status->value)->toBe('past_due')
        ->and($subscription->retry_count)->toBe(8);

    Queue::assertPushed(SendFailedPaymentNotification::class);
});

function invoicePaymentFailedPayload(string $subscriptionId, int $attemptCount, ?int $nextPaymentAttempt, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_failed_'.strtolower($eventId),
                'object' => 'invoice',
                'subscription' => $subscriptionId,
                'attempt_count' => $attemptCount,
                'next_payment_attempt' => $nextPaymentAttempt,
                'created' => now()->timestamp,
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

it('sets stripe_onboarded_at when account becomes onboarded via webhook', function () {
    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_onboarded_webhook',
        'stripe_onboarded' => false,
        'stripe_onboarded_at' => null,
    ]);

    $payload = accountUpdatedPayload('acct_onboarded_webhook', true, 'evt_account_onboarded');

    (new ProcessStripeWebhook($payload))->handle();

    $organization->refresh();

    expect($organization->stripe_onboarded)->toBeTrue()
        ->and($organization->stripe_onboarded_at)->not->toBeNull();
});

it('does not overwrite existing stripe_onboarded_at on subsequent account webhooks', function () {
    $onboardedAt = now()->subDays(5);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_already_onboarded',
        'stripe_onboarded' => true,
        'stripe_onboarded_at' => $onboardedAt,
    ]);

    $payload = accountUpdatedPayload('acct_already_onboarded', true, 'evt_account_already_onboarded');

    (new ProcessStripeWebhook($payload))->handle();

    $organization->refresh();

    expect($organization->stripe_onboarded)->toBeTrue()
        ->and($organization->stripe_onboarded_at->toDateTimeString())->toBe($onboardedAt->toDateTimeString());
});

it('rejects webhook with invalid payload', function () {
    $response = $this->call('POST', route('stripe.webhook'), content: 'not-json', server: [
        'HTTP_Stripe-Signature' => 't=123,v1=whatever',
    ]);

    $response->assertStatus(400);
});

function accountUpdatedPayload(string $accountId, bool $chargesEnabled, string $eventId): string
{
    return json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'account.updated',
        'data' => [
            'object' => [
                'id' => $accountId,
                'object' => 'account',
                'charges_enabled' => $chargesEnabled,
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}
