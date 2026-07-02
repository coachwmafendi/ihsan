<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
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

    $component->assertSet('period', '30_days');

    $component->set('period', '7_days')->assertSet('period', '7_days');

    expect($component->donationTrend)->toBeArray();
    expect($component->campaignsBreakdown)->toBeArray();
    expect($component->donationSizes)->toBeArray();
    expect($component->paymentMethods)->toBeArray();
    expect($component->recentDonations)->toBeCollection();
});
