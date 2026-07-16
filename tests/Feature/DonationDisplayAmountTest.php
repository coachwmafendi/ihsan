<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeDisplayDonation(array $attributes): Donation
{
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    return Donation::factory()->for($campaign)->for($donor)->create($attributes);
}

it('formats MYR amounts with the MYR prefix like the donation detail page', function () {
    $donation = makeDisplayDonation([
        'currency' => 'myr',
        'gross_amount' => 100.00,
        'donor_fee_covered' => 2.50,
    ]);

    expect($donation->display_donation_amount)->toBe('MYR 100.00')
        ->and($donation->display_payment_amount)->toBe('MYR 102.50')
        ->and($donation->display_fee_covered)->toBe('MYR 2.50');
});

it('formats foreign amounts with symbol and currency code like the detail page', function () {
    $donation = makeDisplayDonation([
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'donor_fee_covered' => 3.75,
        'base_amount' => null,
        'exchange_rate' => null,
    ]);

    expect($donation->display_donation_amount)->toBe('$ 50.00 USD')
        ->and($donation->display_payment_amount)->toBe('$ 53.75 USD')
        ->and($donation->display_fee_covered)->toBe('$ 3.75 USD');
});

it('appends the MYR conversion once the exchange rate is synced', function () {
    $donation = makeDisplayDonation([
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'donor_fee_covered' => 3.75,
        'base_amount' => 222.50,
        'exchange_rate' => 4.45,
    ]);

    expect($donation->display_donation_amount)->toBe('$ 50.00 USD (≈ MYR 222.50)')
        ->and($donation->display_payment_amount)->toBe('$ 53.75 USD (≈ MYR 239.19)');
});

it('shows the net amount in MYR once settled, matching the payout amount on the detail page', function () {
    $settled = makeDisplayDonation([
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'net_amount' => 230.10,
        'base_amount' => 222.50,
        'exchange_rate' => 4.45,
    ]);

    $unsettled = makeDisplayDonation([
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'net_amount' => 52.50,
        'base_amount' => null,
        'exchange_rate' => null,
    ]);

    expect($settled->formatted_net_amount)->toBe('MYR 230.10')
        ->and($unsettled->formatted_net_amount)->toBe('$ 52.50 USD');
});
