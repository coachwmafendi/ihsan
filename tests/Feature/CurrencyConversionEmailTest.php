<?php

use App\Mail\DonationReceipt;
use App\Mail\NewDonationNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

uses()->group('currency-conversion');

test('total charged with conversion includes fee converted for non myr donations', function () {
    $donation = Donation::factory()->create([
        'currency' => 'sgd',
        'gross_amount' => 100.00,
        'donor_fee_covered' => 3.50,
        'base_amount' => 316.94,
        'exchange_rate' => 3.16943,
    ]);

    expect($donation->total_charged_with_conversion)->toBe('SGD 103.50 (≈ MYR 328.03)')
        ->and($donation->total_charged)->toBe(103.50);
});

test('total charged with conversion returns original amount for myr donations', function () {
    $donation = Donation::factory()->create([
        'currency' => 'myr',
        'gross_amount' => 150.00,
        'donor_fee_covered' => 5.00,
    ]);

    expect($donation->total_charged_with_conversion)->toBe('MYR 155.00');
});

test('new donation notification email subject and body include currency conversion', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Wakaf Pembinaan Kompleks Masjid Fasa 1',
    ]);
    $donor = Donor::factory()->create(['name' => 'Ahmad Hasbullah']);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'sgd',
        'gross_amount' => 100.00,
        'donor_fee_covered' => 3.50,
        'base_amount' => 316.94,
        'exchange_rate' => 3.16943,
    ]);

    $mailable = new NewDonationNotification($donation, 'unused');

    expect($mailable->envelope()->subject)
        ->toContain('SGD 103.50 (≈ MYR 328.03)')
        ->toContain('Ahmad Hasbullah')
        ->toContain('Wakaf Pembinaan Kompleks Masjid Fasa 1');

    $rendered = $mailable->render();
    expect($rendered)->toContain('SGD 103.50 (≈ MYR 328.03)');
});

test('donor receipt email shows converted total for non myr donations', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'sgd',
        'gross_amount' => 300.00,
        'donor_fee_covered' => 9.50,
        'base_amount' => 950.83,
        'exchange_rate' => 3.16943,
    ]);

    $mailable = new DonationReceipt($donation);

    $rendered = $mailable->render();
    expect($rendered)->toContain('SGD 309.50 (≈ MYR 980.94)');
});
