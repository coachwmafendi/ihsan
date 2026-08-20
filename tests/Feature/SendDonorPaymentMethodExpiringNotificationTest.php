<?php

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Console\Commands\SendPaymentMethodExpiryNotifications;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorPaymentMethodExpiringNotification;
use App\Mail\DonorPaymentMethodExpiringNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->travelTo('2026-08-01');
});

it('renders the payment method expiring email', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Ismail']);

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'amount' => 50.00,
        'currency' => 'myr',
    ]);

    $mailable = new DonorPaymentMethodExpiringNotification(
        collect([$subscription]),
        30
    );

    expect($mailable->envelope()->subject)
        ->toBe('Action required: your card expires in 30 days — Test Org');

    $html = $mailable->render();

    expect($html)
        ->toContain('Your Card Is Expiring Soon')
        ->toContain('Hi Ahmad Ismail')
        ->toContain('Test Campaign')
        ->toContain('MYR 50.00')
        ->toContain('Visa ****4242')
        ->toContain('08/2026')
        ->toContain('expires in 30 days on 08/2026')
        ->toContain('Update my card')
        ->toContain('donorportal/'.($organization->code).'/login/');
});

it('lists multiple subscriptions when one card is used for several campaigns', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaignA = Campaign::factory()->for($organization)->create(['title' => 'Campaign A']);
    $campaignB = Campaign::factory()->for($organization)->create(['title' => 'Campaign B']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Ismail']);

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscriptionA = Subscription::factory()->for($campaignA)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'amount' => 50.00,
        'currency' => 'myr',
    ]);

    $subscriptionB = Subscription::factory()->for($campaignB)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'amount' => 75.00,
        'currency' => 'myr',
    ]);

    $mailable = new DonorPaymentMethodExpiringNotification(
        collect([$subscriptionA, $subscriptionB]),
        30
    );

    $html = $mailable->render();

    expect($html)
        ->toContain('Campaign A')
        ->toContain('Campaign B')
        ->toContain('MYR 50.00')
        ->toContain('MYR 75.00')
        ->toContain('You have 2 recurring donations using this card:');
});

it('sends the expiring notification from the job and logs it', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    (new SendDonorPaymentMethodExpiringNotification([$subscription->getKey()], 30))->handle();

    Mail::assertQueued(DonorPaymentMethodExpiringNotification::class, 1);

    expect(DonorEmailLog::query()
        ->where('subscription_id', $subscription->getKey())
        ->where('mailable_class', DonorPaymentMethodExpiringNotification::class)
        ->count())
        ->toBe(1);
});

it('does not send the expiring notification twice for the same window', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $job = new SendDonorPaymentMethodExpiringNotification([$subscription->getKey()], 30);
    $job->handle();
    $job->handle();

    Mail::assertQueued(DonorPaymentMethodExpiringNotification::class, 1);
});

it('does not send the expiring notification when the donor has opted out', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    (new SendDonorPaymentMethodExpiringNotification([$subscription->getKey()], 30))->handle();

    Mail::assertNothingQueued();
});

it('sends both the 30-day and 7-day notifications for the same payment method', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    (new SendDonorPaymentMethodExpiringNotification([$subscription->getKey()], 30))->handle();
    (new SendDonorPaymentMethodExpiringNotification([$subscription->getKey()], 7))->handle();

    Mail::assertQueued(DonorPaymentMethodExpiringNotification::class, 2);
});

it('dispatches a single grouped notification for multiple subscriptions sharing a card', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $campaignA = Campaign::factory()->for($organization)->create();
    $campaignB = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscriptionA = Subscription::factory()->for($campaignA)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $subscriptionB = Subscription::factory()->for($campaignB)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Dispatched: 1');

    Queue::assertPushed(
        SendDonorPaymentMethodExpiringNotification::class,
        function ($job) use ($subscriptionA, $subscriptionB) {
            return in_array($subscriptionA->getKey(), $job->subscriptions, true)
                && in_array($subscriptionB->getKey(), $job->subscriptions, true)
                && $job->daysRemaining === 30;
        }
    );

    Queue::assertPushed(SendDonorPaymentMethodExpiringNotification::class, 1);
});

it('dispatches 30-day notifications from the command', function () {
    Queue::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Dispatched: 1');

    Queue::assertPushed(
        SendDonorPaymentMethodExpiringNotification::class,
        fn ($job) => $job->subscriptions === [$subscription->getKey()] && $job->daysRemaining === 30
    );
});

it('dispatches 7-day notifications from the command', function () {
    Queue::fake();

    $this->travelTo('2026-08-24');

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Dispatched: 1');

    Queue::assertPushed(
        SendDonorPaymentMethodExpiringNotification::class,
        fn ($job) => $job->subscriptions === [$subscription->getKey()] && $job->daysRemaining === 7
    );
});

it('skips chip-backed subscriptions', function () {
    Queue::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'chip_recurring_token' => 'chip_recurring_123',
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Dispatched: 0');

    Queue::assertNothingPushed();
});

it('skips cancelled or paused subscriptions', function () {
    Queue::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'status' => SubscriptionStatus::Cancelled,
        'cancelled_at' => now(),
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Dispatched: 0');

    Queue::assertNothingPushed();
});

it('skips cards that are not expiring within 30 days', function () {
    Queue::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Dispatched: 0');

    Queue::assertNothingPushed();
});

it('skips already expired cards', function () {
    Queue::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 7,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class)
        ->assertSuccessful()
        ->expectsOutputToContain('Dispatched: 0');

    Queue::assertNothingPushed();
});

it('supports dry run mode', function () {
    Queue::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
    ]);

    $this->artisan(SendPaymentMethodExpiryNotifications::class, ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('[dry-run]');

    Queue::assertNothingPushed();
});

it('can preview and resend the logged expiring notification', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_123',
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 8,
        'exp_year' => 2026,
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'amount' => 75.00,
        'currency' => 'myr',
    ]);

    (new SendDonorPaymentMethodExpiringNotification([$subscription->getKey()], 7))->handle();

    $log = $donor->fresh()->emailLogs()->first();

    $html = app(PreviewDonorEmail::class)->handle($log);

    expect($html)
        ->not->toBeNull()
        ->toContain('Your Card Is Expiring Soon')
        ->toContain('MYR 75.00');

    $newLog = app(ResendDonorEmail::class)->handle($log);

    expect($newLog)
        ->not->toBeNull()
        ->resent_from_id->toBe($log->getKey())
        ->mailable_class->toBe(DonorPaymentMethodExpiringNotification::class);

    Mail::assertQueued(DonorPaymentMethodExpiringNotification::class, function (DonorPaymentMethodExpiringNotification $mail) use ($subscription) {
        return $mail->subscriptions->contains('id', $subscription->getKey());
    });
});
