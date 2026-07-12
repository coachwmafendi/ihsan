<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignEdit;
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
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donor = Donor::factory()->create();
});

it('counts only succeeded donations on the campaign index', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
    ]);
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
    ]);
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Pending,
    ]);
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Failed,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSee('2');
});

it('counts only succeeded donations on the campaign edit page', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
    ]);
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
    ]);
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Pending,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->assertSee('Donations')
        ->assertSee('2');
});
