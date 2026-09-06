<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Mail\FeeDriftAlert;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;

/**
 * The foreign-currency rates were wrong for months and nothing noticed, because
 * nothing compared what we quote against what Stripe actually charged. This
 * check closes that: it reads the settled fee off recent donations and reports
 * when it drifts away from the rate the estimator would quote today.
 */
beforeEach(function () {
    Mail::fake();
    config([
        'app.admin_email' => 'admin@ihsan.test',
        'services.stripe.processing_fee_percent' => 2.5,
    ]);

    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donor = Donor::factory()->create();
});

/**
 * @param  float  $feePercent  The rate Stripe actually charged.
 */
function donationChargedAt(float $feePercent, string $currency = 'myr', string $cardCountry = 'MY', float $amount = 100): Donation
{
    $exchangeRate = $currency === 'myr' ? 1.0 : 3.19;
    $chargedInMyr = $amount * $exchangeRate;

    return Donation::factory()
        ->for(test()->campaign)
        ->for(test()->donor)
        ->create([
            'status' => DonationStatus::Succeeded,
            'currency' => $currency,
            'gross_amount' => $amount,
            'donor_fee_covered' => 0,
            'exchange_rate' => $exchangeRate,
            'donor_country' => $cardCountry,
            'stripe_charge_id' => 'ch_'.uniqid(),
            'stripe_fee' => round($chargedInMyr * $feePercent + 1.00, 2),
            'created_at' => now()->subDay(),
        ]);
}

it('stays quiet while the quoted rates match what Stripe charged', function () {
    donationChargedAt(0.03, 'myr', 'MY');
    donationChargedAt(0.04, 'myr', 'SG');
    donationChargedAt(0.06, 'sgd', 'SG');

    $this->artisan('ihsan:check-fee-drift')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('reports when Stripe charges more than the quoted rate', function () {
    // Every SGD donation settling a point above what we quote: the exact shape
    // of the bug this check exists to catch.
    foreach (range(1, 5) as $ignored) {
        donationChargedAt(0.07, 'sgd', 'SG');
    }

    $this->artisan('ihsan:check-fee-drift')->assertSuccessful();

    Mail::assertQueued(FeeDriftAlert::class);
});

it('ignores a single odd donation among healthy ones', function () {
    foreach (range(1, 20) as $ignored) {
        donationChargedAt(0.03, 'myr', 'MY');
    }
    donationChargedAt(0.09, 'myr', 'MY');

    $this->artisan('ihsan:check-fee-drift')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('leaves donations outside the window alone', function () {
    donationChargedAt(0.07, 'sgd', 'SG')->update(['created_at' => now()->subMonth()]);

    $this->artisan('ihsan:check-fee-drift')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('says so when there is nothing to measure', function () {
    $this->artisan('ihsan:check-fee-drift')
        ->expectsOutputToContain('No settled donations')
        ->assertSuccessful();

    Mail::assertNothingQueued();
});
