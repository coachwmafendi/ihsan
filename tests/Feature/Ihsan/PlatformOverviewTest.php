<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Pages\PlatformOverview;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Fraud\BlockedDonation;
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

it('renders the admin operations layout', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get('/admin/platform-overview')
        ->assertSuccessful()
        ->assertSee('ihsan-admin-page', false)
        ->assertSee('ihsan-admin-metric-card', false);
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

it('calculates MRR from active subscriptions', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();

    // monthly: 100, weekly: 50 → 50 * 4.33 = 216.5, yearly: 120 → 120 / 12 = 10
    Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Active,
        'interval' => 'monthly',
        'amount' => 100.00,
    ]);
    Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Active,
        'interval' => 'weekly',
        'amount' => 50.00,
    ]);
    Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Active,
        'interval' => 'yearly',
        'amount' => 120.00,
    ]);
    // cancelled — must NOT count
    Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Cancelled,
        'interval' => 'monthly',
        'amount' => 999.00,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($user);

    // MRR = 100 + (50 * 4.33) + (120 / 12) = 100 + 216.5 + 10 = 326.50
    Livewire::test(PlatformOverview::class)
        ->assertSet('estimatedMrr', '326.50');
});

it('calculates MTD donation volume with MoM change', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();

    // This month
    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'base_amount' => 200.00,
        'created_at' => now()->startOfMonth()->addDays(1),
    ]);
    // Last month
    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'base_amount' => 100.00,
        'created_at' => now()->subMonth()->startOfMonth()->addDays(1),
    ]);
    // Failed — must NOT count
    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Failed,
        'base_amount' => 999.00,
        'created_at' => now()->startOfMonth()->addDays(1),
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(PlatformOverview::class)
        ->assertSet('donationsThisMonth', '200.00')
        ->assertSet('donationsLastMonth', '100.00')
        ->assertSet('donationsMomChange', 100.0); // (200-100)/100 * 100
});

it('calculates MTD processing fees with MoM change', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'base_amount' => 100.00,
        'created_at' => now()->startOfMonth()->addDays(1),
    ]);
    $donationLast = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'base_amount' => 100.00,
        'created_at' => now()->subMonth()->startOfMonth()->addDays(1),
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => $donation->id,
        'organization_id' => $org->id,
        'fee_amount' => 6.00,
        'status' => 'paid',
        'created_at' => now()->startOfMonth()->addDays(1),
    ]);
    ProcessingFee::factory()->create([
        'donation_id' => $donationLast->id,
        'organization_id' => $org->id,
        'fee_amount' => 3.00,
        'status' => 'paid',
        'created_at' => now()->subMonth()->startOfMonth()->addDays(1),
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(PlatformOverview::class)
        ->assertSet('processingFeesThisMonth', '6.00')
        ->assertSet('processingFeesLastMonth', '3.00')
        ->assertSet('processingFeesMomChange', 100.0);
});

it('counts operational alert metrics', function () {
    $org = Organization::factory()->create(['status' => 'active', 'stripe_onboarded' => false]);
    Organization::factory()->create(['status' => 'active', 'stripe_onboarded' => true]);
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Failed,
        'base_amount' => 50.00,
    ]);

    // Pending blocked donation
    BlockedDonation::factory()->create([
        'donation_id' => $donation->id,
        'review_status' => 'pending',
    ]);

    // Past-due subscription
    Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::PastDue,
        'amount' => 30.00,
        'interval' => 'monthly',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(PlatformOverview::class)
        ->assertSet('pendingBlockedDonations', 1)
        ->assertSet('pastDueSubscriptions', 1)
        ->assertSet('awaitingStripeOnboarding', 1); // org with status=active, stripe_onboarded=false
});

it('calculates donor and subscription health metrics', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $campaign = Campaign::factory()->for($org)->create();

    // 2 new donors this month
    $donor1 = Donor::factory()->create(['created_at' => now()->startOfMonth()->addDays(1)]);
    $donor2 = Donor::factory()->create(['created_at' => now()->startOfMonth()->addDays(2)]);
    // 1 donor last month
    $donor3 = Donor::factory()->create(['created_at' => now()->subMonth()->startOfMonth()->addDays(1)]);

    // donor1 has 2 succeeded donations → repeat donor
    Donation::factory()->for($campaign)->for($donor1)->create(['status' => DonationStatus::Succeeded]);
    Donation::factory()->for($campaign)->for($donor1)->create(['status' => DonationStatus::Succeeded]);
    // donor2 has 1 → not repeat

    // 2 new subs this month, 1 cancelled this month
    Subscription::factory()->for($campaign)->for($donor1)->create([
        'status' => SubscriptionStatus::Active,
        'interval' => 'monthly',
        'amount' => 50.00,
        'created_at' => now()->startOfMonth()->addDays(1),
    ]);
    Subscription::factory()->for($campaign)->for($donor2)->create([
        'status' => SubscriptionStatus::Active,
        'interval' => 'monthly',
        'amount' => 50.00,
        'created_at' => now()->startOfMonth()->addDays(2),
    ]);
    Subscription::factory()->for($campaign)->for($donor3)->create([
        'status' => SubscriptionStatus::Cancelled,
        'interval' => 'monthly',
        'amount' => 50.00,
        'created_at' => now()->subMonth()->startOfMonth()->addDays(1),
        'cancelled_at' => now()->startOfMonth()->addDays(3),
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(PlatformOverview::class)
        ->assertSet('newDonorsThisMonth', 2)
        ->assertSet('newDonorsLastMonth', 1)
        ->assertSet('newDonorsMomChange', 100.0)
        ->assertSet('repeatDonorRate', 33.3) // 1 repeat out of 3 total donors
        ->assertSet('newSubscriptionsThisMonth', 2)
        ->assertSet('cancelledSubscriptionsThisMonth', 1)
        ->assertSet('netSubscriptionChange', 1);
});
