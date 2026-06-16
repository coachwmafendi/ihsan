<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Livewire\App\Insights;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

it('calculates ngo insights scoped to the users organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);
    $donor = Donor::factory()->create();

    $campaign = Campaign::factory()->for($organization)->create();
    $otherCampaign = Campaign::factory()->for($otherOrganization)->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => null,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 30.00,
        'base_amount' => null,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 10.00,
        'base_amount' => null,
        'status' => DonationStatus::Failed,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($otherCampaign)->for($donor)->create([
        'gross_amount' => 999.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 30.00,
        'status' => SubscriptionStatus::Active,
        'interval' => 'monthly',
    ]);

    $this->actingAs($user);

    Livewire::test(Insights::class)
        ->assertOk()
        ->assertSet('stats.total_amount', 130.00)
        ->assertSet('stats.total_count', 2)
        ->assertSet('stats.active_campaigns', 1)
        ->assertSet('stats.total_donors', 1)
        ->assertSet('stats.active_subscriptions', 1)
        ->assertSee('Total Donations')
        ->assertSee('Donation Trend')
        ->assertSee('Payment Methods')
        ->assertSee('Recent Donations');
});

it('switches period filter and exposes computed data arrays', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Insights::class);

    $component->assertSet('period', '30_days');

    $component->set('period', '7_days')->assertSet('period', '7_days');

    expect($component->donationTrend)->toBeArray();
    expect($component->campaignsBreakdown)->toBeArray();
    expect($component->donationSizes)->toBeArray();
    expect($component->paymentMethods)->toBeArray();
    expect($component->recentDonations)->toBeCollection();
});
