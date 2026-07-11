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

it('approximates foreign currency totals using the base amount ratio', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);
    $donor = Donor::factory()->create();

    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => 450.00,
        'processing_fee' => 3.00,
        'net_amount' => 97.00,
        'status' => DonationStatus::Succeeded,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Transactions::class)
        ->call('applyFilters', '', '', '', '', '', '', '', '', false, '')
        ->assertSet('filtersApplied', true)
        ->assertSet('totals.amount', 450.00)
        ->assertSet('totals.fee', 13.5)
        ->assertSet('totals.org_receives', 436.5);
});
