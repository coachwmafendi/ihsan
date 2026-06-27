<?php

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Jobs\ProcessStripeWebhook;
use App\Jobs\SendDonorSubscriptionCancelledNotification;
use App\Mail\DonorSubscriptionCancelledNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

test('donor subscription cancelled email renders correctly', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Ismail']);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 50.00,
        'currency' => 'myr',
        'payment_count' => 3,
    ]);

    $mailable = new DonorSubscriptionCancelledNotification($subscription);

    expect($mailable->envelope()->subject)
        ->toBe('Your recurring donation has been cancelled — Test Org');

    $html = $mailable->render();

    expect($html)
        ->toContain('Recurring Donation Cancelled')
        ->toContain('Hi Ahmad Ismail')
        ->toContain('Test Campaign')
        ->toContain('RM 50.00')
        ->toContain('3')
        ->toContain($subscription->public_id)
        ->toContain('donor/notifications/'.$donor->public_id);
});

test('job sends donor subscription cancelled email once and logs it', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 50.00,
        'currency' => 'myr',
    ]);

    $job = new SendDonorSubscriptionCancelledNotification($subscription);
    $job->handle();
    $job->handle();

    Mail::assertQueued(DonorSubscriptionCancelledNotification::class, 1);

    expect(DonorEmailLog::query()
        ->where('subscription_id', $subscription->getKey())
        ->where('mailable_class', DonorSubscriptionCancelledNotification::class)
        ->count())
        ->toBe(1);
});

test('job does not send email when donor has opted out', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();

    $job = new SendDonorSubscriptionCancelledNotification($subscription);
    $job->handle();

    Mail::assertNothingQueued();
});

test('webhook dispatches donor subscription cancelled notification when subscription is deleted', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'stripe_subscription_id' => 'sub_webhook_cancel_123',
    ]);

    $payload = json_encode([
        'id' => 'evt_sub_cancel_123',
        'object' => 'event',
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => 'sub_webhook_cancel_123',
                'object' => 'subscription',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    (new ProcessStripeWebhook($payload))->handle();

    Queue::assertPushed(SendDonorSubscriptionCancelledNotification::class, fn ($job) => $job->subscription->is($subscription));
});

test('logged donor subscription cancelled email can be previewed and resent', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 75.00,
        'currency' => 'myr',
        'payment_count' => 5,
    ]);

    (new SendDonorSubscriptionCancelledNotification($subscription))->handle();

    $log = $donor->fresh()->emailLogs()->first();

    $html = app(PreviewDonorEmail::class)->handle($log);

    expect($html)
        ->not->toBeNull()
        ->toContain('Recurring Donation Cancelled')
        ->toContain('RM 75.00');

    $newLog = app(ResendDonorEmail::class)->handle($log);

    expect($newLog)
        ->not->toBeNull()
        ->resent_from_id->toBe($log->getKey())
        ->mailable_class->toBe(DonorSubscriptionCancelledNotification::class);

    Mail::assertQueued(DonorSubscriptionCancelledNotification::class, function (DonorSubscriptionCancelledNotification $mail) use ($subscription) {
        return $mail->subscription->is($subscription);
    });
});
