<?php

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;

it('downloads a CSV monthly donation report for an organization', function () {
    $org = Organization::factory()->create([
        'name' => 'Test Organization',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($org)->create(['title' => 'Qurban 2026']);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 250.00,
        'base_amount' => 250.00,
        'stripe_fee' => 4.50,
        'processing_fee' => 3.00,
        'net_amount' => 242.50,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->startOfMonth()->addDay(),
    ]);

    $user = User::factory()->for($org)->create();
    $periodSlug = now()->startOfMonth()->toDateString().'-to-'.now()->endOfMonth()->toDateString();
    $expectedFilename = "ihsan-{$org->public_id}-test-organization-monthly-donations-{$periodSlug}.csv";

    $response = $this->actingAs($user)
        ->get(route('app.reports.monthly-donations.download', [
            'format' => 'csv',
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)
        ->toContain('Test Organization')
        ->toContain($org->public_id)
        ->toContain('Monthly Donation Report')
        ->toContain('Qurban 2026')
        ->toContain('250.00')
        ->toContain('242.50')
        ->toContain('7.50');
});

it('downloads a PDF monthly donation report for an organization', function () {
    $org = Organization::factory()->create([
        'name' => 'Another Organization',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($org)->create(['title' => 'Ramadan Fund']);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'stripe_fee' => 2.00,
        'processing_fee' => 1.50,
        'net_amount' => 96.50,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subMonth()->day(15),
    ]);

    $user = User::factory()->for($org)->create();
    $lastMonth = now()->subMonth();
    $periodSlug = $lastMonth->startOfMonth()->toDateString().'-to-'.$lastMonth->endOfMonth()->toDateString();
    $expectedFilename = "ihsan-{$org->public_id}-another-organization-monthly-donations-{$periodSlug}.pdf";

    $response = $this->actingAs($user)
        ->get(route('app.reports.monthly-donations.download', [
            'format' => 'pdf',
            'dateFrom' => $lastMonth->startOfMonth()->toDateString(),
            'dateTo' => $lastMonth->endOfMonth()->toDateString(),
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)->toContain('%PDF');
});

it('returns 404 for unknown format', function () {
    $org = Organization::factory()->create(['stripe_onboarded' => true]);
    $user = User::factory()->for($org)->create();

    $this->actingAs($user)
        ->get(route('app.reports.monthly-donations.download', ['format' => 'xlsx']))
        ->assertNotFound();
});

it('denies download to users without an organization', function () {
    $user = User::factory()->create(['organization_id' => null]);

    $this->actingAs($user)
        ->get(route('app.reports.monthly-donations.download', ['format' => 'csv']))
        ->assertForbidden();
});

it('swaps date range when from is greater than to', function () {
    $org = Organization::factory()->create([
        'name' => 'Swapped Dates Org',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'status' => DonationStatus::Succeeded,
        'created_at' => '2026-01-10 12:00:00',
    ]);

    $user = User::factory()->for($org)->create();
    $periodSlug = '2026-01-01-to-2026-01-31';
    $expectedFilename = "ihsan-{$org->public_id}-swapped-dates-org-monthly-donations-{$periodSlug}.csv";

    $response = $this->actingAs($user)
        ->get(route('app.reports.monthly-donations.download', [
            'format' => 'csv',
            'dateFrom' => '2026-01-31',
            'dateTo' => '2026-01-01',
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    expect($response->streamedContent())->toContain('50.00');
});
