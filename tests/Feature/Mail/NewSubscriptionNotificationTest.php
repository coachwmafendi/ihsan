<?php

use App\Mail\NewSubscriptionNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

it('builds the new subscription email subject with MYR amount, donor and campaign', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Ramadan Fund']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Rosli']);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'donor_id' => $donor->getKey(),
        'gross_amount' => 250.00,
        'base_amount' => 250.00,
        'currency' => 'myr',
    ]);

    $mailable = new NewSubscriptionNotification($donation, 'RM 250.00');

    $mailable->assertHasSubject('New Recurring Subscription RM 250.00 by Ahmad Rosli on Ramadan Fund — '.config('app.name'));
});

it('builds the new subscription email subject with foreign currency and approximate MYR', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Ramadan Fund']);
    $donor = Donor::factory()->create(['name' => 'Sarah Lim']);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'donor_id' => $donor->getKey(),
        'gross_amount' => 50.00,
        'base_amount' => 220.00,
        'currency' => 'usd',
    ]);

    $mailable = new NewSubscriptionNotification($donation, '$ 50.00 (≈ MYR 220.00)');

    $mailable->assertHasSubject('New Recurring Subscription $ 50.00 (≈ MYR 220.00) by Sarah Lim on Ramadan Fund — '.config('app.name'));
});
