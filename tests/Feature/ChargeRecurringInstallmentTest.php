<?php

use App\Actions\Stripe\ChargeRecurringInstallment;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonorDunningNotification;
use App\Jobs\SendDonorRecurringPaymentNotification;
use App\Jobs\SendFailedPaymentNotification;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\SubscriptionSchedule;
use Illuminate\Support\Facades\Queue;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;

beforeEach(function (): void {
    Stripe::setApiKey('sk_test_fake');
    config(['services.stripe.secret' => 'sk_test_fake']);
});

afterEach(function (): void {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

function makeStripeClient(string $scenario, string $paymentIntentId, string $paymentMethodId): ClientInterface
{
    return new class($scenario, $paymentIntentId, $paymentMethodId) implements ClientInterface
    {
        public function __construct(
            private string $scenario,
            private string $paymentIntentId,
            private string $paymentMethodId,
        ) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            if (str_ends_with($absUrl, '/v1/payment_methods/'.$this->paymentMethodId) && $method === 'get') {
                return [json_encode([
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
                    'billing_details' => null,
                ]), 200, []];
            }

            if (str_ends_with($absUrl, '/v1/payment_intents') && $method === 'post') {
                return [json_encode(match ($this->scenario) {
                    'success' => $this->successPaymentIntent(),
                    'duplicate' => $this->successPaymentIntent(),
                    'requires_action' => [
                        'id' => $this->paymentIntentId,
                        'object' => 'payment_intent',
                        'status' => 'requires_action',
                        'client_secret' => 'pi_secret_test',
                        'payment_method' => $this->paymentMethodId,
                    ],
                    'failure' => [
                        'id' => $this->paymentIntentId,
                        'object' => 'payment_intent',
                        'status' => 'requires_payment_method',
                        'payment_method' => $this->paymentMethodId,
                        'last_payment_error' => [
                            'code' => 'card_declined',
                            'message' => 'Your card was declined.',
                        ],
                    ],
                    default => throw new RuntimeException('Unexpected scenario: '.$this->scenario),
                }), 200, []];
            }

            throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl);
        }

        private function successPaymentIntent(): array
        {
            return [
                'id' => $this->paymentIntentId,
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'payment_method' => $this->paymentMethodId,
                'latest_charge' => [
                    'id' => 'ch_success',
                    'object' => 'charge',
                    'amount' => 5000,
                    'currency' => 'myr',
                    'balance_transaction' => [
                        'id' => 'bt_success',
                        'object' => 'balance_transaction',
                        'amount' => 5000,
                        'fee' => 275,
                        'fee_details' => [
                            ['type' => 'stripe_fee', 'amount' => 150, 'currency' => 'myr', 'description' => 'Stripe processing fee'],
                            ['type' => 'application_fee', 'amount' => 125, 'currency' => 'myr', 'description' => 'Platform fee'],
                        ],
                    ],
                ],
            ];
        }
    };
}

function createDueSubscription(): Subscription
{
    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_recurring_test',
        'country' => 'MY',
    ]);

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_recurring_visa',
        'brand' => 'Visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2030,
        'country' => 'MY',
        'is_default' => true,
    ]);

    return Subscription::factory()->for($campaign)->for($donor)->create([
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
}

it('creates a new donation and advances the subscription after a successful charge', function (): void {
    Queue::fake();

    $subscription = createDueSubscription();
    $campaign = $subscription->campaign;
    $paymentIntentId = 'pi_recurring_success';

    ApiRequestor::setHttpClient(makeStripeClient('success', $paymentIntentId, $subscription->donorPaymentMethod->stripe_payment_method_id));

    $result = app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($result->status)->toBe('succeeded')
        ->and($result->donation)->not->toBeNull();

    $donation = $result->donation;

    expect($donation)
        ->subscription_id->toBe($subscription->getKey())
        ->campaign_id->toBe($campaign->getKey())
        ->donor_id->toBe($subscription->donor_id)
        ->type->toBe(DonationType::Recurring)
        ->status->toBe(DonationStatus::Succeeded)
        ->gross_amount->toBeNumeric()->toEqual(50.00)
        ->stripe_payment_intent_id->toBe($paymentIntentId);

    $campaign->refresh();
    expect((float) $campaign->collected_amount)->toEqual(50.00);

    $subscription->refresh();
    expect($subscription)
        ->status->toBe(SubscriptionStatus::Active)
        ->payment_count->toBe(2)
        ->retry_count->toBe(0)
        ->failed_installment_count->toBe(0)
        ->last_charge_at->not->toBeNull()
        ->last_charge_attempt_at->not->toBeNull()
        ->next_charge_at->not->toBeNull();

    expect($subscription->next_charge_at->format('Y-m-d'))
        ->toBe(SubscriptionSchedule::nextChargeAt(now()->toImmutable(), $subscription->interval)->format('Y-m-d'));

    Queue::assertPushed(SendCampaignMilestoneNotification::class);
    Queue::assertPushed(SendNewDonationNotification::class);
    Queue::assertPushed(SendDonorRecurringPaymentNotification::class);
    Queue::assertPushed(SendLargeDonationNotification::class);
    Queue::assertPushed(SendMetaConversionEvent::class);
    Queue::assertPushed(SendLinkedInConversionEvent::class);
    Queue::assertPushed(SendXAdsConversionEvent::class);
    Queue::assertPushed(SendSnapchatConversionEvent::class);
});

it('handles duplicate payment intent ids without creating a second donation', function (): void {
    Queue::fake();

    $subscription = createDueSubscription();
    $paymentIntentId = 'pi_recurring_duplicate';
    $paymentMethodId = $subscription->donorPaymentMethod->stripe_payment_method_id;

    Donation::factory()->for($subscription->campaign)->for($subscription->donor)->create([
        'subscription_id' => $subscription->getKey(),
        'stripe_payment_intent_id' => $paymentIntentId,
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    ApiRequestor::setHttpClient(makeStripeClient('duplicate', $paymentIntentId, $paymentMethodId));

    $result = app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($result->status)->toBe('succeeded')
        ->and($result->donation)->not->toBeNull();

    expect(Donation::query()->where('stripe_payment_intent_id', $paymentIntentId)->count())->toBe(1);
    expect($subscription->fresh()->payment_count)->toBe(1);

    Queue::assertNothingPushed();
});

it('updates retry schedule and sends dunning notification after a failed charge', function (): void {
    Queue::fake();

    $subscription = createDueSubscription();
    $paymentIntentId = 'pi_recurring_failure';
    $paymentMethodId = $subscription->donorPaymentMethod->stripe_payment_method_id;

    ApiRequestor::setHttpClient(makeStripeClient('failure', $paymentIntentId, $paymentMethodId));

    $result = app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('card_declined');

    expect(Donation::query()->where('stripe_payment_intent_id', $paymentIntentId)->count())->toBe(0);

    $subscription->refresh();
    expect($subscription)
        ->status->toBe(SubscriptionStatus::PastDue)
        ->last_failure_message->toBe('Your card was declined.')
        ->retry_count->toBe(1)
        ->last_charge_attempt_at->not->toBeNull()
        ->next_charge_at->format('Y-m-d')->toBe(now()->addDay()->format('Y-m-d'));

    Queue::assertPushed(SendFailedPaymentNotification::class);
    Queue::assertPushed(SendDonorDunningNotification::class, function ($job) use ($subscription) {
        return $job->subscription->is($subscription) && $job->retryCount === 1;
    });
});

it('marks subscription as failed and dispatches final dunning on terminal failure', function (): void {
    Queue::fake();

    $subscription = createDueSubscription();
    $subscription->update([
        'retry_count' => 3,
        'failed_installment_count' => 5,
    ]);
    $paymentIntentId = 'pi_recurring_terminal_failure';
    $paymentMethodId = $subscription->donorPaymentMethod->stripe_payment_method_id;

    ApiRequestor::setHttpClient(makeStripeClient('failure', $paymentIntentId, $paymentMethodId));

    $result = app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('card_declined');

    $subscription->refresh();
    expect($subscription)
        ->status->toBe(SubscriptionStatus::Failed)
        ->last_failure_message->toBe('Your card was declined.')
        ->next_charge_at->toBeNull();

    Queue::assertPushed(SendFailedPaymentNotification::class);
    Queue::assertPushed(SendDonorDunningNotification::class, function ($job) use ($subscription) {
        return $job->subscription->is($subscription) && $job->retryCount === 4 && $job->isFinalAttempt === true;
    });
});

it('pauses schedule for authentication when a charge requires action', function (): void {
    Queue::fake();

    $subscription = createDueSubscription();
    $paymentIntentId = 'pi_recurring_action';
    $paymentMethodId = $subscription->donorPaymentMethod->stripe_payment_method_id;

    ApiRequestor::setHttpClient(makeStripeClient('requires_action', $paymentIntentId, $paymentMethodId));

    $result = app(ChargeRecurringInstallment::class)->handle($subscription);

    expect($result->status)->toBe('requires_action')
        ->and($result->clientSecret)->toBe('pi_secret_test');

    expect(Donation::query()->where('stripe_payment_intent_id', $paymentIntentId)->count())->toBe(0);

    $subscription->refresh();
    expect($subscription)
        ->status->toBe(SubscriptionStatus::PastDue)
        ->last_charge_attempt_at->not->toBeNull()
        ->next_charge_at->format('Y-m-d')->toBe(now()->addDay()->format('Y-m-d'));

    Queue::assertNothingPushed();
});
