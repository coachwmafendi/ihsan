<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Jobs\SendDonorPaymentMethodChangedNotification;
use App\Jobs\SendPaymentMethodChangedNotification;
use App\Mail\DonorPaymentMethodChangedNotification;
use App\Mail\PaymentMethodChangedNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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

function fakeStripeClientForPaymentMethod(string $paymentMethodId, ?string $customerId = 'cus_test'): ClientInterface
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

            if (str_contains($absUrl, '/v1/customers/') && $method === 'get') {
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
                        'brand' => 'mastercard',
                        'last4' => '5555',
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

it('renders the donor payment method changed email', function () {
    $campaign = Campaign::factory()->for($this->organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Ismail']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 50.00,
        'currency' => 'myr',
    ]);

    $oldPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_old',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2025,
        'is_default' => false,
    ]);

    $newPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_new',
        'brand' => 'mastercard',
        'last4' => '5555',
        'exp_month' => 12,
        'exp_year' => 2030,
        'is_default' => true,
    ]);

    $mailable = new DonorPaymentMethodChangedNotification($subscription, $oldPaymentMethod, $newPaymentMethod);

    expect($mailable->envelope()->subject)
        ->toBe('Your payment method has been updated — '.$this->organization->name);

    $html = $mailable->render();

    expect($html)
        ->toContain('Payment Method Updated')
        ->toContain('Hi Ahmad Ismail')
        ->toContain('Test Campaign')
        ->toContain('Visa ****4242')
        ->toContain('Mastercard ****5555')
        ->toContain('MYR 50.00')
        ->toContain('View my subscriptions');
});

it('sends donor payment method changed notification from the job', function () {
    Mail::fake();

    $campaign = Campaign::factory()->for($this->organization)->create();
    $donor = Donor::factory()->create();

    $oldPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_old',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2025,
        'is_default' => false,
    ]);

    $newPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_new',
        'brand' => 'mastercard',
        'last4' => '5555',
        'exp_month' => 12,
        'exp_year' => 2030,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $newPaymentMethod->getKey(),
    ]);

    (new SendDonorPaymentMethodChangedNotification($subscription, $oldPaymentMethod, $newPaymentMethod))->handle();

    Mail::assertQueued(DonorPaymentMethodChangedNotification::class, 1);

    expect(DonorEmailLog::query()
        ->where('subscription_id', $subscription->getKey())
        ->where('mailable_class', DonorPaymentMethodChangedNotification::class)
        ->count())
        ->toBe(1);
});

it('sends admin payment method changed notification from the job', function () {
    Mail::fake();

    $admin = User::factory()->create([
        'organization_id' => $this->organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($this->organization)->create();
    $donor = Donor::factory()->create();

    $oldPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_old',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2025,
        'is_default' => false,
    ]);

    $newPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_new',
        'brand' => 'mastercard',
        'last4' => '5555',
        'exp_month' => 12,
        'exp_year' => 2030,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $newPaymentMethod->getKey(),
    ]);

    (new SendPaymentMethodChangedNotification($subscription, $oldPaymentMethod, $newPaymentMethod))->handle();

    Mail::assertQueued(PaymentMethodChangedNotification::class, function (PaymentMethodChangedNotification $mail) use ($admin) {
        return $mail->hasTo($admin->email);
    });
});

it('dispatches payment method changed notifications when supporter updates payment method', function () {
    Queue::fake();

    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_test']);
    $subscription = ($this->createSubscription)($donor, ['donor_payment_method_id' => null]);

    ApiRequestor::setHttpClient(fakeStripeClientForPaymentMethod('pm_test_123'));

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

    Queue::assertPushed(SendDonorPaymentMethodChangedNotification::class);
    Queue::assertPushed(SendPaymentMethodChangedNotification::class);
});

it('does not send donor payment method changed notification when supporter opts out', function () {
    Mail::fake();

    $campaign = Campaign::factory()->for($this->organization)->create();
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);

    $newPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_new',
        'brand' => 'mastercard',
        'last4' => '5555',
        'exp_month' => 12,
        'exp_year' => 2030,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $newPaymentMethod->getKey(),
    ]);

    (new SendDonorPaymentMethodChangedNotification($subscription, null, $newPaymentMethod))->handle();

    Mail::assertNothingQueued();
});

it('respects admin notification settings for payment method changed', function () {
    Mail::fake();

    $this->organization->update([
        'settings' => array_merge($this->organization->settings ?? [], [
            'notify_payment_method_changed' => false,
        ]),
    ]);

    $campaign = Campaign::factory()->for($this->organization)->create();
    $donor = Donor::factory()->create();

    $newPaymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_new',
        'brand' => 'mastercard',
        'last4' => '5555',
        'exp_month' => 12,
        'exp_year' => 2030,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $newPaymentMethod->getKey(),
    ]);

    (new SendPaymentMethodChangedNotification($subscription, null, $newPaymentMethod))->handle();

    Mail::assertNothingQueued();
});
