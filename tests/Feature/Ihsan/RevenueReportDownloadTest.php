<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\User;

it('downloads a CSV revenue report for an organization', function () {
    $org = Organization::factory()->create(['status' => 'active', 'name' => 'Test Org']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'stripe_fee' => 1.50,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => Donation::first()->id,
        'organization_id' => $org->id,
        'fee_amount' => 2.50,
        'status' => 'paid',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $period = 'this_month';
    $periodSlug = now()->startOfMonth()->toDateString().'-to-'.now()->endOfMonth()->toDateString();
    $expectedFilename = "ihsan-{$org->public_id}-test-org-revenue-{$periodSlug}-".now()->format('Y-m-d').'.csv';

    $response = $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report', [
            'organizationPublicId' => $org->public_id,
            'format' => 'csv',
            'period' => $period,
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)
        ->toContain('Test Org')
        ->toContain($org->public_id)
        ->toContain('Total Stripe fees')
        ->toContain('Total Ihsan processing fees')
        ->toContain('100.00')
        ->toContain('1.50')
        ->toContain('2.50');
});

it('downloads a PDF revenue report for an organization', function () {
    $org = Organization::factory()->create(['status' => 'active', 'name' => 'Another Org']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    $lastMonth = now()->subMonth()->day(15);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 500.00,
        'base_amount' => 500.00,
        'stripe_fee' => 8.75,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'created_at' => $lastMonth,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => Donation::first()->id,
        'organization_id' => $org->id,
        'fee_amount' => 12.50,
        'status' => 'paid',
        'created_at' => $lastMonth,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $period = 'last_month';
    $lastMonth = now()->subMonth();
    $periodSlug = $lastMonth->startOfMonth()->toDateString().'-to-'.$lastMonth->endOfMonth()->toDateString();
    $expectedFilename = "ihsan-{$org->public_id}-another-org-revenue-{$periodSlug}-".now()->format('Y-m-d').'.pdf';

    $response = $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report', [
            'organizationPublicId' => $org->public_id,
            'format' => 'pdf',
            'period' => $period,
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)->toContain('%PDF');
});

it('returns 404 for unknown format', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report', [
            'organizationPublicId' => $org->public_id,
            'format' => 'xlsx',
            'period' => 'this_month',
        ]))
        ->assertNotFound();
});

it('returns 404 for unknown organization public id', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report', [
            'organizationPublicId' => 'UNKNOWN',
            'format' => 'csv',
            'period' => 'this_month',
        ]))
        ->assertNotFound();
});

it('denies download to ngo admins', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create(['role' => UserRole::NgoAdmin]);

    $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report', [
            'organizationPublicId' => $org->public_id,
            'format' => 'csv',
            'period' => 'this_month',
        ]))
        ->assertForbidden();
});

it('downloads a CSV revenue report for all organizations', function () {
    $orgA = Organization::factory()->create(['status' => 'active', 'name' => 'Alpha Org']);
    $orgB = Organization::factory()->create(['status' => 'active', 'name' => 'Beta Org']);

    $campaignA = Campaign::factory()->for($orgA)->create();
    $campaignB = Campaign::factory()->for($orgB)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaignA)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'stripe_fee' => 1.50,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($campaignB)->for($donor)->create([
        'gross_amount' => 300.00,
        'base_amount' => 300.00,
        'stripe_fee' => 4.50,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => Donation::where('campaign_id', $campaignA->id)->first()->id,
        'organization_id' => $orgA->id,
        'fee_amount' => 2.50,
        'status' => 'paid',
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => Donation::where('campaign_id', $campaignB->id)->first()->id,
        'organization_id' => $orgB->id,
        'fee_amount' => 7.50,
        'status' => 'paid',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $period = 'this_month';
    $dateRange = now()->startOfMonth()->toDateString().' to '.now()->endOfMonth()->toDateString();
    $periodSlug = str_replace(' ', '-', $dateRange);
    $expectedFilename = "ihsan-platform-revenue-{$periodSlug}-".now()->format('Y-m-d').'.csv';

    $response = $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report.all', [
            'format' => 'csv',
            'period' => $period,
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)
        ->toContain('Platform Revenue Report')
        ->toContain('Alpha Org')
        ->toContain('Beta Org')
        ->toContain('100.00')
        ->toContain('300.00')
        ->toContain('2.50')
        ->toContain('7.50')
        ->toContain('Total')
        ->toContain('Total Ihsan processing fees')
        ->toContain('400.00'); // summary total volume
});

it('downloads a PDF revenue report for all organizations', function () {
    $org = Organization::factory()->create(['status' => 'active', 'name' => 'All Orgs Report']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 250.00,
        'base_amount' => 250.00,
        'stripe_fee' => 3.75,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => Donation::first()->id,
        'organization_id' => $org->id,
        'fee_amount' => 6.25,
        'status' => 'paid',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $period = 'this_month';
    $dateRange = now()->startOfMonth()->toDateString().' to '.now()->endOfMonth()->toDateString();
    $periodSlug = str_replace(' ', '-', $dateRange);
    $expectedFilename = "ihsan-platform-revenue-{$periodSlug}-".now()->format('Y-m-d').'.pdf';

    $response = $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report.all', [
            'format' => 'pdf',
            'period' => $period,
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)->toContain('%PDF');
});

it('returns 404 for unknown all-organizations format', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report.all', [
            'format' => 'xlsx',
            'period' => 'this_month',
        ]))
        ->assertNotFound();
});

it('denies all-organizations download to ngo admins', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create(['role' => UserRole::NgoAdmin]);

    $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report.all', [
            'format' => 'csv',
            'period' => 'this_month',
        ]))
        ->assertForbidden();
});
