<?php

use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
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
    $this->subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => 'monthly',
        'status' => 'active',
    ]);
});

it('redirects unauthenticated users from the export route', function () {
    $this->get(route('app.recurring-plans.export', [
        'format' => 'csv',
        'fields' => ['public_id', 'created_at'],
    ]))->assertRedirect(route('login'));
});

it('exports subscriptions as csv with selected fields', function () {
    $response = $this->actingAs($this->user)->get(route('app.recurring-plans.export', [
        'format' => 'csv',
        'fields' => ['public_id', 'created_at', 'status', 'donor.name'],
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('Subscription ID');
    expect($content)->toContain('Created');
    expect($content)->toContain('Status');
    expect($content)->toContain('Supporter Name');
    expect($content)->toContain($this->subscription->public_id);
    expect($content)->toContain($this->donor->name);
});

it('exports subscriptions as xls with selected fields', function () {
    $response = $this->actingAs($this->user)->get(route('app.recurring-plans.export', [
        'format' => 'xls',
        'fields' => ['public_id', 'status'],
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.ms-excel');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('<Workbook');
    expect($content)->toContain('Subscription ID');
    expect($content)->toContain('Status');
    expect($content)->toContain($this->subscription->public_id);
});

it('only exports subscriptions belonging to the users organization', function () {
    $otherOrganization = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);
    $otherSubscription = Subscription::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'donor_id' => $this->donor->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('app.recurring-plans.export', [
        'format' => 'csv',
        'fields' => ['public_id'],
    ]));

    $response->assertOk();

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain($this->subscription->public_id);
    expect($content)->not->toContain($otherSubscription->public_id);
});

it('returns redirect when no fields are selected', function () {
    $this->actingAs($this->user)
        ->get(route('app.recurring-plans.export', [
            'format' => 'csv',
        ]))
        ->assertRedirect(route('app.recurring-plans'));
});
