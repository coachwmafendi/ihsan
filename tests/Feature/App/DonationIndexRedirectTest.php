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
