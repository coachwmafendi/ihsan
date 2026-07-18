<?php

use App\Enums\SubscriptionStatus;
use App\Livewire\App\Supporters\SupporterIndex;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
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
});

function makeSupporterWithDonations(Campaign $campaign, int $donations = 1, array $donationAttributes = []): Donor
{
    $donor = Donor::factory()->create();

    Donation::factory()->count($donations)->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        ...$donationAttributes,
    ]);

    return $donor;
}

it('renders the supporters index with summary cards', function () {
    makeSupporterWithDonations($this->campaign);

    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertStatus(200)
        ->assertSee('Total Supporters')
        ->assertSee('Repeat Supporters')
        ->assertSee('Recurring Supporters')
        ->assertSee('Avg Lifetime Giving');
});

it('resets search and date filters at once', function () {
    makeSupporterWithDonations($this->campaign);

    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertDontSee('Reset filters')
        ->set('search', 'zara')
        ->set('period', '7_days')
        ->assertSee('Reset filters')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('period', 'all_time')
        ->assertDontSee('Reset filters');
});

it('marks avg lifetime giving as approximate when non-MYR donations exist', function () {
    makeSupporterWithDonations($this->campaign, donationAttributes: [
        'currency' => 'usd',
        'gross_amount' => 20.00,
        'base_amount' => 90.00,
    ]);

    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertStatus(200)
        ->assertSeeInOrder(['Avg Lifetime Giving', '≈ MYR 90.00']);
});

it('displays repeat supporters count with percentage of all supporters', function () {
    makeSupporterWithDonations($this->campaign, donations: 3);
    makeSupporterWithDonations($this->campaign, donations: 2);
    makeSupporterWithDonations($this->campaign, donations: 1);
    makeSupporterWithDonations($this->campaign, donations: 1);

    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertStatus(200)
        ->assertSee('50% of all supporters');
});

it('displays recurring supporters with active plans', function () {
    $recurringDonor = makeSupporterWithDonations($this->campaign);
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $recurringDonor->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $cancelledDonor = makeSupporterWithDonations($this->campaign);
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $cancelledDonor->id,
        'status' => SubscriptionStatus::Cancelled,
    ]);

    makeSupporterWithDonations($this->campaign);

    $component = Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertStatus(200)
        ->assertSee('Recurring Supporters');

    expect($component->instance()->recurringSupportersCount)->toBe(1);
});

it('displays average lifetime giving per supporter', function () {
    $donorA = Donor::factory()->create();
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $donorA->id,
        'base_amount' => 100.00,
    ]);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $donorA->id,
        'base_amount' => 200.00,
    ]);

    makeSupporterWithDonations($this->campaign, donationAttributes: ['base_amount' => 50.00]);

    // (100 + 200 + 50) / 2 supporters = 175.00
    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertStatus(200)
        ->assertSee('MYR 175.00');
});

it('shows new supporters this month trend on the total supporters card', function () {
    // Long-time supporter with an old donation and one this month — not new.
    $existingDonor = makeSupporterWithDonations($this->campaign, donationAttributes: ['created_at' => now()->subMonths(2)]);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $existingDonor->id,
        'created_at' => now()->startOfMonth()->addDay(),
    ]);

    // First-time supporter this month — counted.
    makeSupporterWithDonations($this->campaign, donationAttributes: ['created_at' => now()->startOfMonth()->addDay()]);

    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertStatus(200)
        ->assertSee('+1 this month');
});
