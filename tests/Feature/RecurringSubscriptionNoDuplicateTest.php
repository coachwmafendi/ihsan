<?php

use App\Actions\Stripe\CreateRecurringSubscription;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Jobs\ProcessStripeWebhook;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\PaymentIntent;
use Stripe\Stripe;

beforeEach(function (): void {
    Stripe::setApiKey('sk_test_fake');
});

afterEach(function (): void {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

function fakeStripeClientForSubscriptionCreation(string $donorEmail, string $paymentIntentId): object
{
    return new class($donorEmail, $paymentIntentId) implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>}> */
        public array $requests = [];

        public function __construct(
            private string $donorEmail,
            private string $paymentIntentId,
        ) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'headers' => $headers,
                'params' => $params,
            ];

            $response = match (true) {
                str_contains($absUrl, '/v1/customers') => [
                    'id' => 'cus_test',
                    'object' => 'customer',
                ],
                str_contains($absUrl, '/v1/payment_methods/') => [
                    'id' => 'pm_test',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                        'country' => 'MY',
                    ],
                ],
                str_contains($absUrl, '/v1/balance_transactions/') => [
                    'id' => 'txn_test',
                    'object' => 'balance_transaction',
                    'fee' => 125,
                    'fee_details' => [
                        [
                            'amount' => 125,
                            'currency' => 'myr',
                            'type' => 'stripe_fee',
                        ],
                    ],
                ],
                str_contains($absUrl, '/v1/payment_intents/'.$this->paymentIntentId) => [
                    'id' => $this->paymentIntentId,
                    'object' => 'payment_intent',
                    'customer' => 'cus_test',
                    'payment_method' => 'pm_test',
                    'metadata' => [
                        'donor_email' => $this->donorEmail,
                    ],
                    'latest_charge' => [
                        'id' => 'ch_test',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'txn_test',
                            'object' => 'balance_transaction',
                            'fee' => 125,
                            'fee_details' => [
                                [
                                    'amount' => 125,
                                    'currency' => 'myr',
                                    'type' => 'stripe_fee',
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
                    'id' => 'prod_test_'.uniqid(),
                    'object' => 'product',
                ],
                str_ends_with($absUrl, '/v1/prices') => [
                    'id' => 'price_test_'.uniqid(),
                    'object' => 'price',
                ],
                str_ends_with($absUrl, '/v1/subscriptions') => [
                    'id' => 'sub_test_'.uniqid(),
                    'object' => 'subscription',
                ],
                default => ['id' => 'unknown', 'object' => 'unknown'],
            };

            return [json_encode($response), 200, []];
        }
    };
}

it('creates only one local subscription when CreateRecurringSubscription is called twice', function (): void {
    $donor = Donor::factory()->create();
    $stripeClient = fakeStripeClientForSubscriptionCreation($donor->email, 'pi_idempotent_test');
    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_connected_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
        'stripe_payment_intent_id' => 'pi_idempotent_test',
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_idempotent_test',
        'object' => 'payment_intent',
        'customer' => 'cus_test',
        'payment_method' => 'pm_test',
    ]);

    $subscription = app(CreateRecurringSubscription::class)->create($donation, $paymentIntent, ['stripe_account' => 'acct_connected_test']);
    $secondSubscription = app(CreateRecurringSubscription::class)->create($donation, $paymentIntent, ['stripe_account' => 'acct_connected_test']);

    expect(Subscription::count())->toBe(1)
        ->and($subscription->id)->toBe($secondSubscription->id)
        ->and($subscription->wasRecentlyCreated)->toBeTrue()
        ->and($secondSubscription->wasRecentlyCreated)->toBeFalse()
        ->and($subscription->payment_count)->toBe(1)
        ->and($subscription->stripe_subscription_id)->not->toBeNull()
        ->and($donation->fresh()?->subscription_id)->toBe($subscription->id);

    $subscriptionCreations = collect($stripeClient->requests)
        ->filter(fn (array $request): bool => str_ends_with($request['url'], '/v1/subscriptions'));

    expect($subscriptionCreations)->toHaveCount(1);
});

it('does not create duplicate subscriptions when donation form and webhook both process the same recurring donation', function (): void {
    $donor = Donor::factory()->create();
    $stripeClient = fakeStripeClientForSubscriptionCreation($donor->email, 'pi_duplicate_race_test');
    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_connected_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
        'stripe_payment_intent_id' => 'pi_duplicate_race_test',
    ]);

    ProcessStripeWebhook::dispatchSync(json_encode([
        'id' => 'evt_duplicate_race_test',
        'object' => 'event',
        'account' => 'acct_connected_test',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_duplicate_race_test',
                'object' => 'payment_intent',
                'customer' => 'cus_test',
                'metadata' => [
                    'donation_id' => (string) $donation->getKey(),
                    'donor_email' => $donor->email,
                    'campaign_id' => (string) $donation->campaign_id,
                    'organization_id' => (string) $donation->campaign?->organization_id,
                ],
                'payment_method' => 'pm_test',
                'charges' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'ch_duplicate_race_test',
                            'object' => 'charge',
                            'balance_transaction' => null,
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_duplicate_race_test',
        'object' => 'payment_intent',
        'customer' => 'cus_test',
        'payment_method' => 'pm_test',
        'latest_charge' => [
            'id' => 'ch_test',
            'object' => 'charge',
            'balance_transaction' => null,
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('confirmPayment', 'pi_duplicate_race_test', $paymentIntent);

    $donation->refresh();

    expect(Subscription::query()->where('donor_id', $donor->id)->where('campaign_id', $campaign->id)->count())->toBe(1)
        ->and($donation->subscription)->not->toBeNull()
        ->and($donation->subscription->payment_count)->toBe(1)
        ->and($donation->subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($donation->subscription->stripe_subscription_id)->not->toBeNull();

    $subscriptionCreations = collect($stripeClient->requests)
        ->filter(fn (array $request): bool => str_ends_with($request['url'], '/v1/subscriptions'));

    expect($subscriptionCreations)->toHaveCount(1);
});
