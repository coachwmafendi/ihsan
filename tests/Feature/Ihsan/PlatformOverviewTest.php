<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Pages\PlatformOverview;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

it('shows platform overview to super admins only', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(route('filament.admin.pages.platform-overview'));
});

it('denies platform overview to ngo admins', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);

    $this->actingAs($user)
        ->get('/admin/platform-overview')
        ->assertForbidden();
});

it('calculates correct platform-wide metrics', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $otherOrg = Organization::factory()->create(['status' => 'pending']);
    $donor = Donor::factory()->create();

    $campaign = Campaign::factory()->for($org)->create();
    $otherCampaign = Campaign::factory()->for($otherOrg)->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($otherCampaign)->for($donor)->create([
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 20.00,
        'base_amount' => 20.00,
        'status' => DonationStatus::Failed,
        'type' => DonationType::OneTime,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => $donation->id,
        'organization_id' => $org->id,
        'fee_amount' => 3.00,
        'status' => 'paid',
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 30.00,
        'status' => SubscriptionStatus::Active,
        'interval' => 'monthly',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user);

    Livewire::test(PlatformOverview::class)
        ->assertOk()
        ->assertSet('totalOrganizations', 2)
        ->assertSet('pendingOrganizations', 1)
        ->assertSet('activeOrganizations', 1)
        ->assertSet('totalDonationsVolume', '150.00')
        ->assertSet('totalDonationsCount', 3)
        ->assertSet('totalProcessingFees', '3.00')
        ->assertSet('activeSubscriptions', 1)
        ->assertSet('totalDonors', 1);
});
