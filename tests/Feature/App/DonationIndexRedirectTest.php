<?php

declare(strict_types=1);

use App\Livewire\App\Donations\DonationIndex;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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
});

it('redirects to the donation show page via navigate when a row is clicked', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->call('redirectToShow', $donation->public_id)
        ->assertRedirect(route('app.donations.show', $donation));
});

it('keeps a long campaign name to one line in the table', function () {
    // Unconstrained, "Dana Pembinaan Masjid Tahfiz Al Ayubi" wrapped to five
    // lines on a phone and set the height of every row around it.
    $campaign = Campaign::factory()->for($this->organization)->create([
        'title' => 'Dana Pembinaan Masjid Tahfiz Al Ayubi',
    ]);
    Donation::factory()->for($campaign)->for($this->donor)->create();

    $this->actingAs($this->user);

    Livewire::test(DonationIndex::class)
        ->assertSeeHtml('block max-w-[10rem] truncate');
});
