<?php

use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\UserRole;
use App\Jobs\SendNewDonationNotification;
use App\Mail\NewDonationNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('one-time donation notification subject and body include key details', function () {
    $organization = Organization::factory()->create(['name' => 'Masjid Al-Hidayah']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Iftar Ramadan']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Rizal']);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 100.00,
        'donor_fee_covered' => 0,
        'type' => DonationType::OneTime,
    ]);

    $mailable = new NewDonationNotification($donation, 'MYR 100.00');

    expect($mailable->envelope()->subject)
        ->toBe('New one-time donation MYR 100.00 by Ahmad Rizal on Iftar Ramadan — Ihsan');

    $html = $mailable->render();

    expect($html)
        ->toContain('New Donation Received')
        ->toContain('Supporter')
        ->toContain('Email')
        ->toContain('Donation ID')
        ->toContain($donor->email)
        ->toContain($donation->public_id)
        ->toContain('View in Ihsan')
        ->toContain('https://app.example.test/donations/'.$donation->public_id)
        ->toContain(myrTime($donation->created_at));
});

test('recurring donation notification subject includes payment number', function () {
    $organization = Organization::factory()->create(['name' => 'Masjid Al-Hidayah']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Iftar Ramadan']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Rizal']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'interval' => SubscriptionInterval::Monthly,
        'payment_count' => 4,
    ]);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 50.00,
        'donor_fee_covered' => 0,
        'type' => DonationType::Recurring,
        'subscription_id' => $subscription->id,
    ]);

    $mailable = new NewDonationNotification($donation, 'MYR 50.00');

    expect($mailable->envelope()->subject)
        ->toBe('4th recurring MYR 50.00 donation by Ahmad Rizal on Iftar Ramadan');
});

test('recurring donation notification email body matches new layout', function () {
    $organization = Organization::factory()->create(['name' => 'Masjid Al-Hidayah']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Iftar Ramadan']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Rizal']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'interval' => SubscriptionInterval::Monthly,
        'payment_count' => 3,
        'current_period_end' => now()->addMonth(),
    ]);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 50.00,
        'donor_fee_covered' => 0,
        'type' => DonationType::Recurring,
        'subscription_id' => $subscription->id,
    ]);

    $mailable = new NewDonationNotification($donation, 'MYR 50.00');
    $html = $mailable->render();

    expect($html)
        ->toContain('You\'ve received your <strong>3rd</strong> recurring donation')
        ->toContain('MYR 50.00')
        ->toContain('Ahmad Rizal')
        ->toContain('Supporter')
        ->toContain('Email')
        ->toContain('Donation ID')
        ->toContain('Net to Organization')
        ->toContain('Next Billing Date')
        ->toContain('View in Ihsan')
        ->toContain('https://app.example.test/donations/'.$donation->public_id)
        ->toContain('Hi <strong>')
        ->not->toContain('Payment Number')
        ->not->toContain('Frequency');
});

test('new donation notification is only sent once even when the job runs twice', function () {
    Mail::fake();

    $organization = Organization::factory()->create([
        'settings' => ['notify_new_donation' => true],
    ]);
    Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);

    $donation = Donation::factory()->for($organization->campaigns->first())->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 100.00,
        'donor_fee_covered' => 0,
        'type' => DonationType::OneTime,
    ]);

    $job = new SendNewDonationNotification($donation);
    $job->handle();
    $job->handle();

    Mail::assertQueued(NewDonationNotification::class, 1);
    expect($donation->fresh()->new_donation_notification_sent_at)->not->toBeNull();
});

test('net amount keeps the donation currency when no exchange rate is synced', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'donor_fee_covered' => 3.75,
        'net_amount' => 52.50,
        'exchange_rate' => null,
        'base_amount' => null,
    ]);

    $html = (new NewDonationNotification($donation, 'USD 53.75'))->render();

    expect($html)->toContain('USD 52.50')
        ->not->toContain('RM 52.50');
});

test('net amount shows MYR once the exchange rate is synced', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'donor_fee_covered' => 3.75,
        'net_amount' => 230.10,
        'exchange_rate' => 4.45,
        'base_amount' => 222.50,
    ]);

    $html = (new NewDonationNotification($donation, 'USD 53.75'))->render();

    expect($html)->toContain('MYR 230.10');
});
