<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

function statementDonation(Campaign $campaign, Donor $donor, string $date, array $attributes = []): Donation
{
    return Donation::factory()->for($campaign)->for($donor)->create(array_merge([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'gross_amount' => 100,
        'currency' => 'myr',
        'created_at' => $date,
    ], $attributes));
}

it('downloads an annual statement pdf for the selected year', function () {
    $org = Organization::factory()->create(['code' => 'testorg']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    statementDonation($campaign, $donor, '2026-03-15 10:00:00');
    statementDonation($campaign, $donor, '2026-08-01 10:00:00');

    $response = $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.annual-statement', ['organization' => $org, 'year' => 2026]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))
        ->toContain('Ihsan-testorg-annual-statement-2026.pdf');
});

it('only includes succeeded donations from the requested year', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create(['title' => 'Tabung Lillah']);
    $donor = Donor::factory()->create();

    statementDonation($campaign, $donor, '2026-06-01 10:00:00', ['gross_amount' => 250]);
    statementDonation($campaign, $donor, '2025-06-01 10:00:00', ['gross_amount' => 999]); // other year
    statementDonation($campaign, $donor, '2026-07-01 10:00:00', ['gross_amount' => 777, 'status' => DonationStatus::Failed]); // not succeeded

    $html = view('emails.donation-annual-statement', [
        'donations' => Donation::query()->where('gross_amount', 250)->get(),
        'organization' => $org,
        'donor' => $donor,
        'year' => 2026,
    ])->render();

    expect($html)
        ->toContain('Annual Donation Statement')
        ->toContain('2026')
        ->toContain('Tabung Lillah')
        ->toContain('MYR 250.00');
});

it('redirects when no donations exist for the year', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    statementDonation($campaign, $donor, '2025-06-01 10:00:00');

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.annual-statement', ['organization' => $org, 'year' => 2026]))
        ->assertRedirect(route('donorportal.donations', $org))
        ->assertSessionHas('error');
});

it('requires a donor session', function () {
    $org = Organization::factory()->create();

    $this->get(route('donorportal.donations.annual-statement', ['organization' => $org, 'year' => 2026]))
        ->assertRedirect(route('donorportal.login', $org));
});
