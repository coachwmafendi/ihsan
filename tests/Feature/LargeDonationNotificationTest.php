<?php

use App\Mail\LargeDonationNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

test('large donation notification subject includes amount donor and campaign', function () {
    $organization = Organization::factory()->create(['name' => 'Masjid Al-Munawwarah']);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Wakaf Pembinaan Kompleks Masjid Fasa 1',
    ]);
    $donor = Donor::factory()->create(['name' => 'Harry Kane']);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'usd',
        'gross_amount' => 223.00,
        'donor_fee_covered' => 6.99,
        'base_amount' => 906.93,
        'exchange_rate' => 4.069,
    ]);

    $mailable = new LargeDonationNotification($donation, '$ 229.99 (≈ MYR 935.37)');

    expect($mailable->envelope()->subject)
        ->toBe('🚨 Large Donation Received — $ 229.99 (≈ MYR 935.37) by Harry Kane on Wakaf Pembinaan Kompleks Masjid Fasa 1 — Ihsan');
});

test('large donation notification subject falls back when relationships are missing', function () {
    $donation = Donation::factory()->create([
        'currency' => 'myr',
        'gross_amount' => 1500.00,
        'donor_fee_covered' => 0,
        'base_amount' => null,
    ]);

    $donation->setRelation('donor', null);
    $donation->setRelation('campaign', null);

    $mailable = new LargeDonationNotification($donation, 'RM 1,500.00');

    expect($mailable->envelope()->subject)
        ->toBe('🚨 Large Donation Received — RM 1,500.00 by a donor on a campaign — Ihsan');
});
