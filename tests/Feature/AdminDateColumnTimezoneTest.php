<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Filament\Pages\ProcessingFees;
use App\Filament\Pages\Transactions;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('names the timezone on the transactions date column', function () {
    Livewire::actingAs($this->admin)
        ->test(Transactions::class)
        ->assertSee('Date (MYT)');
});

it('does not repeat the timezone on every transactions row', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subHour(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(Transactions::class)
        ->assertSee('Date (MYT)')
        ->assertSee(myrTime($donation->created_at, false))
        ->assertDontSee(myrTime($donation->created_at, true));
});

it('names the timezone on the processing fees date column', function () {
    Livewire::actingAs($this->admin)
        ->test(ProcessingFees::class)
        ->assertSee('Date (MYT)');
});

it('dates a processing fee by Malaysian time, not UTC', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'status' => DonationStatus::Succeeded,
    ]);

    // 9 Sep 2026, 03:00 in Kuala Lumpur is still 8 Sep in UTC. The column has
    // to show the Malaysian day, the one the money actually moved on here.
    ProcessingFee::factory()->create([
        'organization_id' => $organization->id,
        'donation_id' => $donation->id,
        'created_at' => '2026-09-08 19:00:00',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProcessingFees::class)
        ->assertSee('09 Sep 2026')
        ->assertDontSee('08 Sep 2026');
});
