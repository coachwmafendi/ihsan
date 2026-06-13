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
        'status' => 'succeeded',
    ]);
});

it('renders the donations index page', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.index'));

    $response->assertStatus(200);
    $response->assertSee('Donations');
    $response->assertSee($this->donor->name);
    $response->assertSee($this->campaign->title);
});

it('renders the donation show page', function () {
    \Livewire\Livewire::actingAs($this->user)
        ->test(\App\Livewire\App\Donations\DonationShow::class, ['donation' => $this->donation])
        ->assertStatus(200)
        ->assertSee($this->donation->public_id)
        ->assertSee($this->donor->name)
        ->assertSee($this->campaign->title);
});

it('filters donations by period', function () {
    $oldDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'created_at' => now()->subMonths(2),
        'status' => 'succeeded',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['period' => 'this_month']));

    $response->assertStatus(200);
    $response->assertSee($this->donation->public_id);
    $response->assertDontSee($oldDonation->public_id);
});

it('filters donations by status', function () {
    $failedDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => 'failed',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['statusFilter' => 'succeeded']));

    $response->assertStatus(200);
    $response->assertSee($this->donation->public_id);
    $response->assertDontSee($failedDonation->public_id);
});

it('searches donations by donor name', function () {
    $otherDonor = Donor::factory()->create(['name' => 'Zara Unique']);
    $otherDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $otherDonor->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['search' => $otherDonor->name]));

    $response->assertStatus(200);
    $response->assertSee($otherDonation->public_id);
    $response->assertDontSee($this->donation->public_id);
});

it('redirects guests to login', function () {
    $this->get(route('app.donations.index'))->assertRedirect('/login');
    $this->get(route('app.donations.show', $this->donation))->assertRedirect('/login');
});
