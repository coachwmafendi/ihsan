<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Livewire\App\Dashboard;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('redirects guests to login', function () {
    get('/app/dashboard')
        ->assertRedirect('/login');
});

it('renders successfully for authenticated users', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Overview of your fundraising activity');
});

it('displays dashboard stats', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Total Donations')
        ->assertSee('Donors')
        ->assertSee('Active Campaigns')
        ->assertSee('Active Subscriptions')
        ->assertSee('Avg Donation');
});

it('shows quick action buttons', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Create Campaign')
        ->assertSee('View Donations')
        ->assertSee('Virtual Terminal')
        ->assertSee('Opens in new tab', false);
});

it('shows analytics sections from merged insights', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Donation Trend')
        ->assertSee('Donations by Campaign')
        ->assertSee('Donation Sizes')
        ->assertSee('Payment Methods')
        ->assertSee('Recent Donations');
});

it('has sidebar navigation', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Fundraise')
        ->assertSee('Finance')
        ->assertSee('Supporters')
        ->assertSee('Organization');
});

it('opens virtual terminal navigation in a new tab', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('href="/app/virtual-terminal"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('Opens in new tab', false);
});

it('dispatches the open campaign modal event from the dashboard', function () {
    Livewire::actingAs($this->user)
        ->test('app.dashboard')
        ->assertSee('Create Campaign')
        ->call('openCreateCampaignModal')
        ->assertDispatched('open-create-campaign-modal');
});

it('calculates stats scoped to the users organization', function () {
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

    Livewire::actingAs($user)
        ->test('app.dashboard')
        ->assertSet('stats.total_amount', 130.00)
        ->assertSet('stats.total_count', 2)
        ->assertSet('stats.active_campaigns', 1)
        ->assertSet('stats.total_donors', 1)
        ->assertSet('stats.active_subscriptions', 1);
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

    $component = Livewire::actingAs($user)
        ->test('app.dashboard');

    $component->assertSet('period', 'today');

    $component->set('period', '7_days')->assertSet('period', '7_days');

    expect($component->donationTrend)->toBeArray();
    expect($component->campaignsBreakdown)->toBeArray();
    expect($component->donationSizes)->toBeArray();
    expect($component->paymentMethods)->toBeArray();
    expect($component->recentDonations)->toBeCollection();
});

it('calculates MRR by normalizing subscription amounts to a monthly equivalent', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);

    Subscription::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'amount' => 100,
        'currency' => 'myr',
    ]);

    Subscription::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Weekly,
        'amount' => 10,
        'currency' => 'myr',
    ]);

    $health = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->instance()
        ->recurringHealth();

    // 100 (monthly) + 10 * 52/12 (weekly normalized) = 100 + 43.333... = 143.33
    expect($health['mrr'])->toEqualWithDelta(143.33, 0.01);
    expect($health['mrr_has_approximation'])->toBeFalse();
});

it('counts only past_due and failed subscriptions as at-risk', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);

    Subscription::factory()->create(['campaign_id' => $campaign->id, 'status' => SubscriptionStatus::PastDue]);
    Subscription::factory()->create(['campaign_id' => $campaign->id, 'status' => SubscriptionStatus::Failed]);
    Subscription::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => SubscriptionStatus::Active,
        'failed_installment_count' => 2,
    ]);
    Subscription::factory()->create(['campaign_id' => $campaign->id, 'status' => SubscriptionStatus::Cancelled]);

    $health = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->instance()
        ->recurringHealth();

    expect($health['at_risk_count'])->toBe(2);
});

it('sums expected charges only for active subscriptions charging within the next 30 days', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);

    Subscription::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 50,
        'currency' => 'myr',
        'next_charge_at' => now()->addDays(10),
    ]);

    Subscription::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 999,
        'currency' => 'myr',
        'next_charge_at' => now()->addDays(45),
    ]);

    $health = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->instance()
        ->recurringHealth();

    expect($health['expected_30_days'])->toEqualWithDelta(50.0, 0.01);
    expect($health['expected_30_days_has_approximation'])->toBeFalse();
});

it('flags approximation when a contributing subscription is not in MYR', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);

    Subscription::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 100,
        'currency' => 'usd',
        'next_charge_at' => now()->addDays(5),
    ]);

    $health = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->instance()
        ->recurringHealth();

    expect($health['mrr_has_approximation'])->toBeTrue();
    expect($health['expected_30_days_has_approximation'])->toBeTrue();
});

it('displays recurring revenue health stat cards', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('MRR')
        ->assertSee('At-risk Subscriptions')
        ->assertSee('Expected (30 days)');
});
