<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Filament\Pages\Transactions;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('sums foreign currency totals without re-converting MYR-stored fees', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);
    $donor = Donor::factory()->create();

    // processing_fee and net_amount are stored in MYR; only gross_amount is in
    // the donation currency (base_amount is its MYR equivalent).
    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => 450.00,
        'exchange_rate' => 4.5,
        'processing_fee' => 11.25,
        'net_amount' => 427.50,
        'status' => DonationStatus::Succeeded,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Transactions::class)
        ->call('applyFilters', '', '', '', '', '', '', '', '', false, '')
        ->assertSet('filtersApplied', true)
        ->assertSet('totals.amount', 450.00)
        ->assertSet('totals.fee', 11.25)
        ->assertSet('totals.org_receives', 427.50);
});

it('labels the processing fee column in MYR with an original currency tooltip', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);
    $donor = Donor::factory()->create();

    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => 407.84,
        'exchange_rate' => 4.078410,
        'processing_fee' => 10.93,
        'net_amount' => 399.04,
        'status' => DonationStatus::Succeeded,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Transactions::class)
        ->loadTable()
        ->assertSee('MYR 10.93')
        ->assertDontSee('USD 10.93')
        ->assertSee('≈ USD 2.68');
});
