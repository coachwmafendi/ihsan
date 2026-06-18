<?php

use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Mail\NewDonationNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;

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

    $mailable = new NewDonationNotification($donation, 'RM 100.00');

    expect($mailable->envelope()->subject)
        ->toBe('New one-time donation RM 100.00 by Ahmad Rizal on Iftar Ramadan — Ihsan');

    $html = $mailable->render();

    expect($html)
        ->toContain('New Donation Received')
        ->toContain('Supporter')
        ->toContain('Email')
        ->toContain('Donation ID')
        ->toContain($donor->email)
        ->toContain($donation->public_id)
        ->toContain('View in Ihsan')
        ->toContain('app/donations/'.$donation->public_id)
        ->toContain($donation->created_at->format('d M Y, h:i A'));
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

    $mailable = new NewDonationNotification($donation, 'RM 50.00');

    expect($mailable->envelope()->subject)
        ->toBe('Recurring payment #4 received RM 50.00 by Ahmad Rizal on Iftar Ramadan — Ihsan');
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

    $mailable = new NewDonationNotification($donation, 'RM 50.00');
    $html = $mailable->render();

    expect($html)
        ->toContain('3rd recurring RM 50.00 donation by Ahmad Rizal on Iftar Ramadan')
        ->toContain('You\'ve received your <strong>3rd</strong> recurring donation')
        ->toContain('/ month')
        ->toContain('Supporter')
        ->toContain('Email')
        ->toContain('Donation ID')
        ->toContain('Net to Organisation')
        ->toContain('Next Billing Date')
        ->toContain('View in Ihsan')
        ->toContain('app/donations/'.$donation->public_id)
        ->not->toContain('Payment Number')
        ->not->toContain('Frequency');
});
