<?php

use App\Models\Campaign;
use App\Models\Donation;
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
});

test('donor index page is accessible by authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('app.donors.index'))
        ->assertOk();
});

test('donor index page lists donors for the organization', function () {
    $donors = Donor::factory()->count(3)->create();
    foreach ($donors as $donor) {
        Donation::factory()->create([
            'donor_id' => $donor->id,
            'campaign_id' => $this->campaign->id,
        ]);
    }

    // Different org donor
    $otherCampaign = Campaign::factory()->create();
    $otherDonor = Donor::factory()->create();
    Donation::factory()->create([
        'donor_id' => $otherDonor->id,
        'campaign_id' => $otherCampaign->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donors.index'));

    $response->assertOk();
    $response->assertSee('Total Donors');
});

test('donor index page supports search', function () {
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ibrahim',
        'email' => 'ahmad@example.com',
    ]);
    Donation::factory()->create([
        'donor_id' => $donor->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donors.index', ['search' => 'Ahmad']));

    $response->assertOk();
    $response->assertSee('Ahmad Ibrahim');
});

test('donor show page displays donor details', function () {
    $donor = Donor::factory()->create([
        'name' => 'Sarah Lim',
        'email' => 'sarah@example.com',
    ]);

    Donation::factory()->count(2)->create([
        'donor_id' => $donor->id,
        'campaign_id' => $this->campaign->id,
        'gross_amount' => 100,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donors.show', $donor));

    $response->assertOk();
    $response->assertSee('Sarah Lim');
    $response->assertSee('Total Donations');
    $response->assertSee('Total Amount');
    $response->assertSee('Active Subscriptions');
    $response->assertSee('Recent Donations');
});

test('donor show page links to virtual terminal', function () {
    $donor = Donor::factory()->create();
    Donation::factory()->create([
        'donor_id' => $donor->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donors.show', $donor));

    $response->assertOk();
    $response->assertSee('View in Virtual Terminal');
});

test('unauthenticated user cannot access donor pages', function () {
    $this->get(route('app.donors.index'))->assertRedirect('/login');

    $donor = Donor::factory()->create();
    Donation::factory()->create([
        'donor_id' => $donor->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $this->get(route('app.donors.show', $donor))->assertRedirect('/login');
});

test('user cannot view donor from different organization', function () {
    $otherCampaign = Campaign::factory()->create();
    $donor = Donor::factory()->create();
    Donation::factory()->create([
        'donor_id' => $donor->id,
        'campaign_id' => $otherCampaign->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.donors.show', $donor))
        ->assertNotFound();
});

test('donor index rows are clickable to donor show', function () {
    $donor = Donor::factory()->create();
    Donation::factory()->create([
        'donor_id' => $donor->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donors.index'));

    $response->assertOk();
    $response->assertSee(route('app.donors.show', $donor));
});

test('donor show page displays recent subscriptions', function () {
    $donor = Donor::factory()->create();
    Donation::factory()->create([
        'donor_id' => $donor->id,
        'campaign_id' => $this->campaign->id,
    ]);

    Subscription::factory()->create([
        'donor_id' => $donor->id,
        'campaign_id' => $this->campaign->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donors.show', $donor));

    $response->assertOk();
    $response->assertSee('Recent Subscriptions');
    $response->assertSee('Active');
});
