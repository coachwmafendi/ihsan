<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorSubscriptionFailedNotification;
use App\Mail\DonorSubscriptionFailedNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;

it('renders the donor subscription failed email', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Ismail']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Failed,
        'payment_count' => 3,
    ]);

    $mailable = new DonorSubscriptionFailedNotification($subscription);

    expect($mailable->envelope()->subject)
        ->toBe('Your recurring donation could not be continued — Test Org');

    $html = $mailable->render();

    expect($html)
        ->toContain('Recurring Donation Ended')
        ->toContain('Hi Ahmad Ismail')
        ->toContain('Test Campaign')
        ->toContain('MYR 50.00')
        ->toContain('Monthly')
        ->toContain('3')
        ->toContain('Start a new recurring donation');
});

it('sends donor subscription failed notification from the job', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Failed,
    ]);

    (new SendDonorSubscriptionFailedNotification($subscription))->handle();

    Mail::assertQueued(DonorSubscriptionFailedNotification::class, 1);

    expect(DonorEmailLog::query()
        ->where('subscription_id', $subscription->getKey())
        ->where('mailable_class', DonorSubscriptionFailedNotification::class)
        ->count())
        ->toBe(1);
});

it('does not send duplicate donor subscription failed notifications', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Failed,
    ]);

    $job = new SendDonorSubscriptionFailedNotification($subscription);
    $job->handle();
    $job->handle();

    Mail::assertQueued(DonorSubscriptionFailedNotification::class, 1);
});

it('does not send donor subscription failed notification when supporter opts out', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Failed,
    ]);

    (new SendDonorSubscriptionFailedNotification($subscription))->handle();

    Mail::assertNothingQueued();
});

it('respects donor notification settings for subscription failed', function () {
    Mail::fake();

    $organization = Organization::factory()->create([
        'settings' => ['notify_supporter_subscription_failed' => false],
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Failed,
    ]);

    (new SendDonorSubscriptionFailedNotification($subscription))->handle();

    Mail::assertNothingQueued();
});
