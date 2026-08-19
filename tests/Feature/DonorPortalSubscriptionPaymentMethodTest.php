<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;

beforeEach(function () {
    Stripe::setApiKey('sk_test_fake');

    $this->organization = Organization::factory()->create();
    $this->donor = Donor::factory()->create();

    $this->authenticateAsDonor = function (Donor $donor): void {
        test()->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $this->organization->getKey()]);
    };

    $this->createSubscription = function (Donor $donor, array $overrides = []): Subscription {
        $campaign = Campaign::factory()->for($this->organization)->create();

        return Subscription::factory()->for($campaign)->for($donor)->create(array_merge([
            'stripe_subscription_id' => null,
            'stripe_price_id' => null,
            'chip_recurring_token' => null,
            'status' => SubscriptionStatus::Active,
            'interval' => SubscriptionInterval::Monthly,
            'amount' => 30.00,
            'currency' => 'myr',
            'next_charge_at' => now()->addDay(),
        ], $overrides));
    };
});

afterEach(function (): void {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

function fakeStripeClientForAppControlledPaymentMethod(string $paymentMethodId, ?string $customerId = 'cus_test'): ClientInterface
{
    return new class($paymentMethodId, $customerId) implements ClientInterface
    {
        public function __construct(
            private string $paymentMethodId,
            private ?string $customerId,
        ) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            if (str_ends_with($absUrl, '/v1/customers') && $method === 'post') {
                return [json_encode([
                    'id' => $this->customerId,
                    'object' => 'customer',
                ]), 200, []];
            }

            if (str_ends_with($absUrl, '/v1/setup_intents') && $method === 'post') {
                return [json_encode([
                    'id' => 'seti_test',
                    'object' => 'setup_intent',
                    'client_secret' => 'seti_test_secret',
                ]), 200, []];
            }

            if (str_ends_with($absUrl, '/v1/payment_methods/'.$this->paymentMethodId) && $method === 'get') {
                return [json_encode([
                    'id' => $this->paymentMethodId,
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                        'exp_month' => 12,
                        'exp_year' => 2030,
                        'country' => 'MY',
                    ],
                    'customer' => $this->customerId,
                ]), 200, []];
            }

            if (str_contains($absUrl, '/v1/payment_methods/'.$this->paymentMethodId.'/attach')) {
                return [json_encode([
                    'id' => $this->paymentMethodId,
                    'object' => 'payment_method',
                    'customer' => $this->customerId,
                ]), 200, []];
            }

            throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl);
        }
    };
}

it('rejects client secret request for chip recurring subscription', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'chip_recurring_token' => 'chip-token-123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->getJson(route('donorportal.subscriptions.payment-method.client-secret', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]))
        ->assertUnprocessable()
        ->assertJson([
            'error' => 'Updating payment method is not supported for CHIP recurring subscriptions.',
        ]);
});

it('rejects payment method update for chip recurring subscription', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'chip_recurring_token' => 'chip-token-123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->postJson(route('donorportal.subscriptions.payment-method.update', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]), [
        'payment_method_id' => 'pm_test_123',
    ])
        ->assertUnprocessable()
        ->assertJson([
            'error' => 'Updating payment method is not supported for CHIP recurring subscriptions.',
        ]);
});

it('returns client secret for app-controlled subscription', function () {
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_test']);
    $subscription = ($this->createSubscription)($donor);

    ApiRequestor::setHttpClient(fakeStripeClientForAppControlledPaymentMethod('pm_test_123'));

    ($this->authenticateAsDonor)($donor);

    $this->getJson(route('donorportal.subscriptions.payment-method.client-secret', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]))
        ->assertOk()
        ->assertJson([
            'client_secret' => 'seti_test_secret',
        ]);
});

it('updates payment method for app-controlled subscription', function () {
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_test']);
    $subscription = ($this->createSubscription)($donor);

    ApiRequestor::setHttpClient(fakeStripeClientForAppControlledPaymentMethod('pm_test_123'));

    ($this->authenticateAsDonor)($donor);

    $this->postJson(route('donorportal.subscriptions.payment-method.update', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]), [
        'payment_method_id' => 'pm_test_123',
    ])
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
        ]);

    $subscription->refresh();

    expect($subscription->donorPaymentMethod)->not->toBeNull()
        ->and($subscription->donorPaymentMethod->stripe_payment_method_id)->toBe('pm_test_123');
});

it('creates a stripe customer and returns client secret for app-controlled subscription without stored customer', function () {
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);
    $subscription = ($this->createSubscription)($donor);

    ApiRequestor::setHttpClient(fakeStripeClientForAppControlledPaymentMethod('pm_test_123', 'cus_new'));

    ($this->authenticateAsDonor)($donor);

    $this->getJson(route('donorportal.subscriptions.payment-method.client-secret', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]))
        ->assertOk()
        ->assertJson([
            'client_secret' => 'seti_test_secret',
        ]);

    $donor->refresh();

    expect($donor->stripe_customer_id)->toBe('cus_new');
});

it('creates a stripe customer when updating payment method for app-controlled subscription without stored customer', function () {
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);
    $subscription = ($this->createSubscription)($donor);

    ApiRequestor::setHttpClient(fakeStripeClientForAppControlledPaymentMethod('pm_test_123', 'cus_new'));

    ($this->authenticateAsDonor)($donor);

    $this->postJson(route('donorportal.subscriptions.payment-method.update', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]), [
        'payment_method_id' => 'pm_test_123',
    ])
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
        ]);

    $donor->refresh();
    $subscription->refresh();

    expect($donor->stripe_customer_id)->toBe('cus_new')
        ->and($subscription->donorPaymentMethod)->not->toBeNull()
        ->and($subscription->donorPaymentMethod->stripe_payment_method_id)->toBe('pm_test_123');
});

it('hides update card button for chip subscription on subscriptions page', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'chip_recurring_token' => 'chip-token-123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->get(route('donorportal.subscriptions', $this->organization))
        ->assertOk()
        ->assertDontSee('openPayment(\''.$subscription->public_id.'\')', false);
});

it('shows update card button for stripe subscription on subscriptions page', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'stripe_subscription_id' => 'sub_test_123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->get(route('donorportal.subscriptions', $this->organization))
        ->assertOk()
        ->assertSee('openPayment(\''.$subscription->public_id.'\')', false);
});

it('shows update card button for app-controlled subscription on subscriptions page', function () {
    $subscription = ($this->createSubscription)($this->donor);

    ($this->authenticateAsDonor)($this->donor);

    $this->get(route('donorportal.subscriptions', $this->organization))
        ->assertOk()
        ->assertSee('openPayment(\''.$subscription->public_id.'\')', false);
});
