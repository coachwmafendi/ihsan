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
    $this->donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'currency' => 'myr',
        'status' => 'succeeded',
    ]);
});

it('redirects unauthenticated users from the export route', function () {
    $this->get(route('app.supporters.export', [
        'format' => 'csv',
        'fields' => ['public_id', 'name'],
    ]))->assertRedirect('/login');
});

it('exports supporters as csv with selected fields', function () {
    $response = $this->actingAs($this->user)->get(route('app.supporters.export', [
        'format' => 'csv',
        'fields' => ['public_id', 'name', 'email', 'lifetime_total_myr'],
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('Supporter ID');
    expect($content)->toContain('Name');
    expect($content)->toContain('Email');
    expect($content)->toContain('Lifetime Total (MYR)');
    expect($content)->toContain($this->donor->public_id);
    expect($content)->toContain('Ahmad Ali');
});

it('exports supporters as xls with selected fields', function () {
    $response = $this->actingAs($this->user)->get(route('app.supporters.export', [
        'format' => 'xls',
        'fields' => ['public_id', 'name'],
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.ms-excel');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('<Workbook');
    expect($content)->toContain('Supporter ID');
    expect($content)->toContain('Name');
    expect($content)->toContain($this->donor->public_id);
});

it('only exports supporters belonging to the users organization', function () {
    $otherOrganization = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);
    $otherDonor = Donor::factory()->create();
    Donation::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'donor_id' => $otherDonor->id,
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'currency' => 'myr',
        'status' => 'succeeded',
    ]);

    $response = $this->actingAs($this->user)->get(route('app.supporters.export', [
        'format' => 'csv',
        'fields' => ['public_id'],
    ]));

    $response->assertOk();

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain($this->donor->public_id);
    expect($content)->not->toContain($otherDonor->public_id);
});

it('returns redirect when no fields are selected', function () {
    $this->actingAs($this->user)
        ->get(route('app.supporters.export', [
            'format' => 'csv',
        ]))
        ->assertRedirect(route('app.supporters.index'));
});
