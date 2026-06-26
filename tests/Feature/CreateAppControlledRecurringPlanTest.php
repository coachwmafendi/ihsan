<?php

use App\Actions\Stripe\CreateAppControlledRecurringPlan;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
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

function fakeStripeClientForAppControlledPlan(string $donorEmail, string $paymentIntentId, string $paymentMethodId): object
{
    return new class($donorEmail, $paymentIntentId, $paymentMethodId) implements ClientInterface
    {
        public function __construct(
            private string $donorEmail,
            private string $paymentIntentId,
            private string $paymentMethodId,
        ) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $response = match (true) {
                str_ends_with($absUrl, '/v1/customers') && $method === 'post' => [
                    'id' => 'cus_app_controlled_test',
                    'object' => 'customer',
                    'email' => $params['email'] ?? $this->donorEmail,
                ],
                str_contains($absUrl, '/v1/payment_methods/'.$this->paymentMethodId) && str_ends_with($absUrl, '/attach') => [
                    'id' => $this->paymentMethodId,
                    'object' => 'payment_method',
                    'type' => 'card',
                    'customer' => 'cus_app_controlled_test',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                        'country' => 'MY',
                        'exp_month' => 12,
                        'exp_year' => 2030,
                    ],
                ],
                str_ends_with($absUrl, '/v1/payment_methods/'.$this->paymentMethodId) && $method === 'get' => [
                    'id' => $this->paymentMethodId,
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
                str_contains($absUrl, '/v1/payment_intents/'.$this->paymentIntentId) => [
                    'id' => $this->paymentIntentId,
                    'object' => 'payment_intent',
                    'customer' => 'cus_app_controlled_test',
                    'payment_method' => $this->paymentMethodId,
                    'metadata' => [
                        'donor_email' => $this->donorEmail,
                    ],
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };
}

it('creates an app-controlled recurring plan without a stripe subscription id', function (): void {
    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'donor_fee_covered' => 0,
        'stripe_payment_intent_id' => 'pi_app_controlled_test',
    ]);

    $paymentMethodId = 'pm_app_controlled_visa';
    ApiRequestor::setHttpClient(fakeStripeClientForAppControlledPlan(
        donorEmail: $donor->email,
        paymentIntentId: 'pi_app_controlled_test',
        paymentMethodId: $paymentMethodId,
    ));

    $paymentIntent = new PaymentIntent('pi_app_controlled_test');
    $paymentIntent->payment_method = $paymentMethodId;
    $paymentIntent->customer = null;
    $paymentIntent->metadata = (object) [
        'donor_email' => $donor->email,
    ];

    $subscription = app(CreateAppControlledRecurringPlan::class)->create(
        $donation,
        $paymentIntent,
        ['stripe_account' => $organization->stripe_account_id],
    );

    expect($subscription)
        ->stripe_subscription_id->toBeNull()
        ->status->toBe(SubscriptionStatus::Active)
        ->amount->toBeNumeric()->toEqual(50.00)
        ->payment_count->toBe(1)
        ->next_charge_at->not->toBeNull()
        ->donor_payment_method_id->not->toBeNull();

    expect($donation->fresh()->subscription_id)->toBe($subscription->getKey());

    $freshDonor = $donor->fresh();
    expect($freshDonor->stripe_customer_id)->toBe('cus_app_controlled_test');

    $paymentMethod = $freshDonor->paymentMethods()->first();
    expect($paymentMethod)
        ->not->toBeNull()
        ->stripe_payment_method_id->toBe($paymentMethodId)
        ->is_default->toBeTrue()
        ->brand->toBe('Visa')
        ->last4->toBe('4242');
});

it('returns existing subscription when donation already has one', function (): void {
    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'donor_fee_covered' => 0,
        'stripe_payment_intent_id' => 'pi_app_controlled_existing_test',
    ]);

    $existingSubscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => null,
        'amount' => 50.00,
        'currency' => 'myr',
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'payment_count' => 1,
    ]);

    $donation->update(['subscription_id' => $existingSubscription->getKey()]);

    $paymentMethodId = 'pm_app_controlled_existing_visa';
    ApiRequestor::setHttpClient(fakeStripeClientForAppControlledPlan(
        donorEmail: $donor->email,
        paymentIntentId: 'pi_app_controlled_existing_test',
        paymentMethodId: $paymentMethodId,
    ));

    $paymentIntent = new PaymentIntent('pi_app_controlled_existing_test');
    $paymentIntent->payment_method = $paymentMethodId;
    $paymentIntent->customer = null;
    $paymentIntent->metadata = (object) [
        'donor_email' => $donor->email,
    ];

    $subscription = app(CreateAppControlledRecurringPlan::class)->create(
        $donation,
        $paymentIntent,
        ['stripe_account' => $organization->stripe_account_id],
    );

    expect($subscription->getKey())->toBe($existingSubscription->getKey());
    expect(Subscription::count())->toBe(1);
});
