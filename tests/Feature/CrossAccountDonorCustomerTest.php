<?php

declare(strict_types=1);

use App\Actions\Stripe\ChargeRecurringInstallment;
use App\Actions\Stripe\ResolveDonorStripeCustomer;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\DonorStripeCustomer;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Queue;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;

/**
 * Regression cover for donors who support more than one organization.
 *
 * Stripe customers exist inside a single connected account, so reusing one
 * donor-level customer id across accounts made Stripe answer "No such customer"
 * on every account but the one it was created on.
 */
beforeEach(function () {
    Stripe::setApiKey('sk_test_fake');
    config(['services.stripe.secret' => 'sk_test_fake']);
    Queue::fake();
});

afterEach(function () {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

/**
 * Rejects any customer that does not belong to the account being addressed,
 * exactly as Stripe does.
 *
 * @param  array<string, string>  $customerByAccount
 */
function accountScopedStripeClient(array $customerByAccount): object
{
    $client = new class($customerByAccount) implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, account: string|null, params: array<string, mixed>}> */
        public array $requests = [];

        public function __construct(private array $customerByAccount) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $account = null;

            foreach ($headers as $header) {
                if (str_starts_with((string) $header, 'Stripe-Account: ')) {
                    $account = substr((string) $header, strlen('Stripe-Account: '));
                }
            }

            $this->requests[] = compact('method', 'account', 'params') + ['url' => $absUrl];

            $ownCustomer = $this->customerByAccount[$account] ?? null;

            if (str_ends_with($absUrl, '/v1/customers') && $method === 'post') {
                return [json_encode(['id' => $ownCustomer, 'object' => 'customer']), 200, []];
            }

            if (preg_match('#/v1/customers/(cus_[A-Za-z0-9_]+)#', $absUrl, $matches) === 1) {
                if ($matches[1] !== $ownCustomer) {
                    return [json_encode(['error' => [
                        'type' => 'invalid_request_error',
                        'message' => "No such customer: '{$matches[1]}'",
                    ]]), 404, []];
                }

                return [json_encode(['id' => $ownCustomer, 'object' => 'customer']), 200, []];
            }

            if (str_contains($absUrl, '/v1/payment_methods/')) {
                return [json_encode([
                    'id' => 'pm_cross_account',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'customer' => $ownCustomer,
                    'card' => ['brand' => 'visa', 'last4' => '4242', 'exp_month' => 12, 'exp_year' => 2030, 'country' => 'MY'],
                ]), 200, []];
            }

            if (str_ends_with($absUrl, '/v1/payment_intents') && $method === 'post') {
                // Stripe refuses a customer that lives on another account.
                if (($params['customer'] ?? null) !== $ownCustomer) {
                    return [json_encode(['error' => [
                        'type' => 'invalid_request_error',
                        'message' => "No such customer: '".($params['customer'] ?? '')."'",
                    ]]), 404, []];
                }

                return [json_encode([
                    'id' => 'pi_cross_account',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'payment_method' => 'pm_cross_account',
                    'latest_charge' => [
                        'id' => 'ch_cross_account',
                        'object' => 'charge',
                        'amount' => 5000,
                        'currency' => 'myr',
                        'balance_transaction' => [
                            'id' => 'bt_cross_account',
                            'object' => 'balance_transaction',
                            'amount' => 5000,
                            'fee' => 275,
                            'fee_details' => [],
                        ],
                    ],
                ]), 200, []];
            }

            throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl);
        }
    };

    ApiRequestor::setHttpClient($client);

    return $client;
}

/**
 * @return array{0: Subscription, 1: Organization}
 */
function dueSubscriptionForOrganization(Donor $donor, Organization $organization, string $paymentMethodId): array
{
    $campaign = Campaign::factory()->for($organization)->create(['collected_amount' => 0]);

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => $paymentMethodId,
        'brand' => 'Visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2030,
        'country' => 'MY',
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
        'next_charge_at' => now()->subDay(),
        'last_charge_at' => null,
        'last_charge_attempt_at' => null,
        'paused_until' => null,
        'cancel_at' => null,
        'payment_count' => 1,
        'retry_count' => 0,
        'failed_installment_count' => 0,
    ]);

    return [$subscription, $organization];
}

it('charges a recurring installment with the customer belonging to that account', function () {
    $orgA = Organization::factory()->stripeConnected()->create();
    $orgB = Organization::factory()->stripeConnected()->create();

    // The donor's legacy column points at org B's customer, which is precisely
    // the state that used to break org A.
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_on_b', 'country' => 'MY']);

    [$subscriptionA] = dueSubscriptionForOrganization($donor, $orgA, 'pm_cross_account');

    DonorStripeCustomer::create([
        'donor_id' => $donor->getKey(),
        'organization_id' => $orgA->getKey(),
        'stripe_customer_id' => 'cus_on_a',
    ]);

    $client = accountScopedStripeClient([
        $orgA->stripe_account_id => 'cus_on_a',
        $orgB->stripe_account_id => 'cus_on_b',
    ]);

    $result = app(ChargeRecurringInstallment::class)->handle($subscriptionA);

    expect($result->status)->toBe('succeeded');

    $chargeRequest = collect($client->requests)
        ->first(fn ($r) => str_ends_with($r['url'], '/v1/payment_intents') && $r['method'] === 'post');

    expect($chargeRequest['params']['customer'])->toBe('cus_on_a')
        ->and($chargeRequest['account'])->toBe($orgA->stripe_account_id);
});

it('heals a missing mapping from the saved payment method instead of failing the charge', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_on_another_account', 'country' => 'MY']);

    [$subscription] = dueSubscriptionForOrganization($donor, $organization, 'pm_cross_account');

    // No DonorStripeCustomer row: the state every subscription is in before the
    // backfill command runs.
    expect(DonorStripeCustomer::query()->count())->toBe(0);

    accountScopedStripeClient([$organization->stripe_account_id => 'cus_real_for_org']);

    $result = app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($result->status)->toBe('succeeded')
        ->and(DonorStripeCustomer::query()
            ->where('donor_id', $donor->getKey())
            ->where('organization_id', $organization->getKey())
            ->value('stripe_customer_id'))->toBe('cus_real_for_org');
});

it('issues a donor portal setup intent without reusing another account customer', function () {
    $orgA = Organization::factory()->stripeConnected()->create();
    $orgB = Organization::factory()->stripeConnected()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_on_b']);

    [$subscriptionA] = dueSubscriptionForOrganization($donor, $orgA, 'pm_cross_account');

    $client = accountScopedStripeClient([
        $orgA->stripe_account_id => 'cus_on_a',
        $orgB->stripe_account_id => 'cus_on_b',
    ]);

    $customerId = app(ResolveDonorStripeCustomer::class)->resolve($donor, $orgA);

    expect($customerId)->toBe('cus_on_a');

    // The legacy id was checked against org A and rejected, then a customer was
    // created on org A rather than reused from org B.
    $methods = collect($client->requests)->map(fn ($r) => $r['method'])->all();

    expect($methods)->toBe(['get', 'post'])
        ->and(collect($client->requests)->pluck('account')->unique()->all())->toBe([$orgA->stripe_account_id])
        ->and($donor->refresh()->stripe_customer_id)->toBe('cus_on_b');

    expect($subscriptionA->donor_id)->toBe($donor->getKey());
});
