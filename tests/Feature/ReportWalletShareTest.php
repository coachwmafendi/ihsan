<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The monthly offer now stands between the amount and the wallet button. It
 * may raise the average gift and it may cost some of the fastest-converting
 * donors there are. Neither is knowable by argument.
 */
beforeEach(function () {
    $this->travelTo('2026-09-10 12:00:00');
    $this->campaign = Campaign::factory()->for(Organization::factory())->create();
});

function donationPaidBy(string $method, string $when, float $amount = 100): Donation
{
    return Donation::factory()->for(test()->campaign)->create([
        'status' => DonationStatus::Succeeded,
        'payment_method_type' => $method,
        'gross_amount' => $amount,
        'base_amount' => $amount,
        'currency' => 'myr',
        'created_at' => $when,
    ]);
}

it('counts wallet payments against everything else, week by week', function () {
    donationPaidBy('apple_pay', '2026-09-08 09:00:00');
    donationPaidBy('google_pay', '2026-09-08 10:00:00');
    donationPaidBy('card', '2026-09-09 09:00:00');
    donationPaidBy('card', '2026-09-09 10:00:00');

    $this->artisan('ihsan:wallet-share', ['--weeks' => 2])
        ->expectsOutputToContain('50.0%')
        ->assertSuccessful();
});

it('marks a week too small to read anything into', function () {
    // One donor swings the share by tens of points; a percentage from two
    // donations is a number pretending to be a measurement.
    donationPaidBy('apple_pay', '2026-09-08 09:00:00');
    donationPaidBy('card', '2026-09-09 09:00:00');

    $this->artisan('ihsan:wallet-share', ['--weeks' => 1])
        ->expectsOutputToContain('*')
        ->assertSuccessful();
});

it('leaves failed attempts out of the share', function () {
    // A wallet payment that never completed is not a wallet donation.
    donationPaidBy('card', '2026-09-08 09:00:00');

    Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Failed,
        'payment_method_type' => 'apple_pay',
        'created_at' => '2026-09-08 10:00:00',
    ]);

    $this->artisan('ihsan:wallet-share', ['--weeks' => 1])
        ->expectsOutputToContain('0.0%')
        ->assertSuccessful();
});

it('says so plainly when there is nothing to report', function () {
    $this->artisan('ihsan:wallet-share')
        ->expectsOutputToContain('No successful donations')
        ->assertSuccessful();
});
