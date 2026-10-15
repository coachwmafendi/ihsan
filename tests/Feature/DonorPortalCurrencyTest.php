<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\File;

/**
 * Most donations through the portal are charged in SGD and settle in ringgit.
 * gross_amount and donor_fee_covered stay in the charge currency, while
 * stripe_fee, processing_fee and net_amount are rewritten in MYR when the
 * charge settles. The portal printed those MYR figures with an SGD label
 * ("Net received SGD 79.91" on a SGD 25 gift), and summed SGD as ringgit.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create(['title' => 'Dana Pembinaan']);
    $this->donor = Donor::factory()->create();
});

function portalDonation(Campaign $campaign, Donor $donor, array $attributes): Donation
{
    return Donation::factory()->for($campaign)->for($donor)->create(array_merge([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'stripe_payment_intent_id' => 'pi_'.fake()->unique()->bothify('????????'),
        'chip_purchase_id' => null,
        'chip_fee' => 0,
    ], $attributes));
}

/** The production donation that was reported, as it sits in the database after settling. */
function settledSgdDonation(Campaign $campaign, Donor $donor): Donation
{
    return portalDonation($campaign, $donor, [
        'currency' => 'sgd',
        'gross_amount' => 25.00,
        'donor_fee_covered' => 2.51,
        'stripe_fee' => 6.27,
        'processing_fee' => 1.60,
        'net_amount' => 79.91,
        'base_currency' => 'myr',
        'base_amount' => 79.77,
        'exchange_rate' => 3.19064,
    ]);
}

function viewDetail($test, Donation $donation): string
{
    return $test->withSession(['donor_id' => $test->donor->getKey(), 'organization_id' => $test->organization->getKey()])
        ->get(route('donorportal.donations.detail', ['organization' => $test->organization, 'donation' => $donation]))
        ->assertOk()
        ->getContent();
}

function asDonor($test)
{
    return $test->withSession(['donor_id' => $test->donor->getKey(), 'organization_id' => $test->organization->getKey()]);
}

it('shows every line of a settled foreign donation in the currency it was charged in', function () {
    $html = viewDetail($this, settledSgdDonation($this->campaign, $this->donor));

    // 6.27 and 1.60 are ringgit; at 3.19064 they are SGD 1.97 and SGD 0.50.
    expect($html)
        ->toContain('SGD 25.00')
        ->toContain('+ SGD 2.51')
        ->toContain('- SGD 1.97')
        ->toContain('- SGD 0.50')
        ->toContain('SGD 25.04')
        ->toContain('≈ MYR 79.91')
        ->not->toContain('SGD 79.91')
        ->not->toContain('SGD 1.60')
        ->not->toContain('SGD 6.27');
});

it('adds the payment processing fee so the breakdown sums to the net', function () {
    $html = viewDetail($this, portalDonation($this->campaign, $this->donor, [
        'currency' => 'myr',
        'gross_amount' => 100.00,
        'donor_fee_covered' => 7.30,
        'stripe_fee' => 5.29,
        'processing_fee' => 2.00,
        'net_amount' => 100.01,
        'base_amount' => 100.00,
        'exchange_rate' => null,
    ]));

    expect($html)
        ->toContain('+ MYR 7.30')
        ->toContain('- MYR 5.29')
        ->toContain('- MYR 2.00')
        ->toContain('MYR 100.01')
        ->not->toContain('≈ MYR');
});

it('leaves an unsettled foreign donation in its charge currency without a ringgit estimate', function () {
    $html = viewDetail($this, portalDonation($this->campaign, $this->donor, [
        'currency' => 'sgd',
        'gross_amount' => 10.00,
        'donor_fee_covered' => 0,
        'stripe_fee' => 0,
        'processing_fee' => 0.20,
        'net_amount' => 9.80,
        'base_amount' => null,
        'exchange_rate' => null,
    ]));

    expect($html)
        ->toContain('- SGD 0.20')
        ->toContain('SGD 9.80')
        ->not->toContain('≈ MYR');
});

it('values "Giving by Campaign" in ringgit, not by adding SGD to MYR', function () {
    settledSgdDonation($this->campaign, $this->donor);
    portalDonation($this->campaign, $this->donor, ['currency' => 'myr', 'gross_amount' => 50.00, 'base_amount' => 50.00, 'exchange_rate' => null]);

    asDonor($this)->get(route('donorportal.dashboard', $this->organization))
        ->assertOk()
        ->assertViewHas('campaignChartData', fn ($rows) => abs((float) $rows->first()->total - 129.77) < 0.01)
        ->assertSee('≈ MYR 129.77');
});

it('converts a foreign donation with a rate but no base amount before adding it to the total given', function () {
    portalDonation($this->campaign, $this->donor, ['currency' => 'sgd', 'gross_amount' => 25.00, 'base_amount' => null, 'exchange_rate' => 3.2]);

    asDonor($this)->get(route('donorportal.dashboard', $this->organization))
        ->assertOk()
        ->assertViewHas('totalGiven', fn ($total) => abs($total - 80.00) < 0.01);
});

it('totals the annual statement in ringgit at each donation\'s own rate', function () {
    portalDonation($this->campaign, $this->donor, ['currency' => 'sgd', 'gross_amount' => 25.00, 'base_amount' => null, 'exchange_rate' => 3.2, 'created_at' => '2026-03-01 10:00:00']);
    portalDonation($this->campaign, $this->donor, ['currency' => 'myr', 'gross_amount' => 50.00, 'base_amount' => 50.00, 'exchange_rate' => null, 'created_at' => '2026-04-01 10:00:00']);

    $html = view('emails.donation-annual-statement', [
        'donations' => Donation::query()->with('campaign.organization')->get(),
        'organization' => $this->organization,
        'donor' => $this->donor,
        'year' => 2026,
    ])->render();

    expect($html)->toContain('MYR 130.00')->not->toContain('MYR 75.00');
});

it('states recurring giving per month whatever the plan\'s interval', function () {
    Subscription::factory()->for($this->campaign)->for($this->donor)->create(['currency' => 'sgd', 'amount' => 10.00, 'interval' => SubscriptionInterval::Weekly]);
    Subscription::factory()->for($this->campaign)->for($this->donor)->create(['currency' => 'sgd', 'amount' => 20.00, 'interval' => SubscriptionInterval::Monthly]);

    asDonor($this)->get(route('donorportal.dashboard', $this->organization))
        ->assertOk()
        ->assertViewHas('monthlyRecurringFormatted', fn ($formatted) => $formatted === ['sgd' => 'SGD 63.33']);
});

/**
 * The bugs above all came from the portal re-deriving currency rules that the
 * Donation model already owns. Keep the portal from doing it again.
 */
it('leaves currency conversion in the portal to the Donation model', function () {
    $files = collect(File::allFiles(resource_path('views/donor')))->map->getPathname()
        ->merge([
            resource_path('views/emails/donation-annual-statement.blade.php'),
            app_path('Http/Controllers/DonorPortalScoping.php'),
            app_path('Http/Controllers/DonorDashboardController.php'),
            app_path('Http/Controllers/DonorDonationController.php'),
        ]);

    $offences = [];

    foreach ($files as $file) {
        $source = File::get($file);

        foreach ([
            '/COALESCE\(\s*(donations\.)?base_amount/i' => 'hand-rolled ringgit conversion; use Donation::reportAmountColumn()',
            '/base_amount\s*\?\?/' => 'hand-rolled ringgit conversion; use report_amount',
            '/->(net_amount|processing_fee|stripe_fee|chip_fee)\b/' => 'settlement figures change currency when a charge settles; use donorBreakdown()',
        ] as $pattern => $reason) {
            if (preg_match($pattern, $source, $match)) {
                $offences[] = str_replace(base_path().'/', '', $file).": {$match[0]} — {$reason}";
            }
        }
    }

    expect($offences)->toBe([]);
});
