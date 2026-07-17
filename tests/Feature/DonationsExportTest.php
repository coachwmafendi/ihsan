<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->donor = Donor::factory()->create();
    $this->donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 150.00,
        'net_amount' => 145.50,
        'currency' => 'myr',
        'status' => 'succeeded',
        'type' => 'one_time',
    ]);
});

it('redirects unauthenticated users from the export route', function () {
    $this->get(route('app.donations.export', [
        'format' => 'csv',
        'fields' => ['public_id', 'created_at'],
    ]))->assertRedirect(route('login'));
});

it('exports donations with empty filter values', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.export', [
        'format' => 'csv',
        'fields' => ['public_id'],
        'search' => '',
        'statusFilter' => '',
        'campaignFilter' => '',
        'frequencyFilter' => '',
        'period' => 'all_time',
        'dateFrom' => '',
        'dateTo' => '',
    ]));

    $response->assertOk();
});

it('exports donations as csv with selected fields', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.export', [
        'format' => 'csv',
        'fields' => ['public_id', 'created_at', 'status', 'donor.name'],
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('Donation ID');
    expect($content)->toContain('Date');
    expect($content)->toContain('Donation Status');
    expect($content)->toContain('Supporter Name');
    expect($content)->toContain($this->donation->public_id);
    expect($content)->toContain($this->donor->name);
});

it('exports donations as xls with selected fields', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.export', [
        'format' => 'xls',
        'fields' => ['public_id', 'status'],
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.ms-excel');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('<Workbook');
    expect($content)->toContain('Donation ID');
    expect($content)->toContain('Donation Status');
    expect($content)->toContain($this->donation->public_id);
});

it('only exports donations belonging to the users organization', function () {
    $otherOrganization = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);
    $otherDonation = Donation::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'donor_id' => $this->donor->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('app.donations.export', [
        'format' => 'csv',
        'fields' => ['public_id'],
    ]));

    $response->assertOk();

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain($this->donation->public_id);
    expect($content)->not->toContain($otherDonation->public_id);
});

it('returns redirect when no fields are selected', function () {
    $this->actingAs($this->user)
        ->get(route('app.donations.export', [
            'format' => 'csv',
        ]))
        ->assertRedirect(route('app.donations.index'));
});
