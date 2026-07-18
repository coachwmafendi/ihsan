<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignIndex;
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
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'collected_amount' => 0,
    ]);
    $this->donor = Donor::factory()->create();
});

it('does not show approximation symbol when all donations are in myr', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'myr',
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
    ]);

    $this->campaign->update(['collected_amount' => 100.00]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSee('MYR 100.00')
        ->assertDontSee('≈ MYR 100.00');
});

it('shows approximation symbol when campaign has non-myr donations', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => 450.00,
    ]);

    $this->campaign->update(['collected_amount' => 450.00]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSee('≈ MYR 450.00');
});

it('shows approximation symbol when campaign has mixed myr and non-myr donations', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'myr',
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
    ]);

    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'sgd',
        'gross_amount' => 50.00,
        'base_amount' => 165.00,
    ]);

    $this->campaign->update(['collected_amount' => 215.00]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSee('≈ MYR 215.00');
});
