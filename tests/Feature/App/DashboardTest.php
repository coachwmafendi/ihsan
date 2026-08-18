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
    get('https://app.example.test/dashboard')
        ->assertRedirect(route('login'));
});

it('renders successfully for authenticated users', function () {
    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Overview of your fundraising activity');
});

it('displays dashboard stats', function () {
    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('Total Donations')
        ->assertSee('Donors')
        ->assertSee('Active Campaigns')
        ->assertSee('Active Subscriptions')
        ->assertSee('Avg Donation');
});

it('marks avg donation as approximate when non-MYR donations exist', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'usd',
        'gross_amount' => 25.00,
        'base_amount' => 115.00,
        'created_at' => now(),
    ]);

    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Avg Donation', '≈ MYR 115.00']);
});

it('shows quick action buttons', function () {
    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('Create Campaign')
        ->assertSee('View Donations')
        ->assertSee('Virtual Terminal')
        ->assertSee('Opens in new tab', false);
});

it('shows analytics sections from merged insights', function () {
    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('Donation Trend')
        ->assertSee('Donations by Campaign')
        ->assertSee('Donation Sizes')
        ->assertSee('Payment Methods')
        ->assertSee('Donations by Frequency')
        ->assertSee('Recent Donations');
});

it('renders an interactive line chart with hover figures for the donation trend', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 250.00,
        'base_amount' => 250.00,
        'created_at' => now(),
    ]);

    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('Donation Trend')
        // Interactive chart scaffolding: SVG path + Alpine hover handlers.
        ->assertSee('vector-effect="non-scaling-stroke"', false)
        ->assertSee('x-ref="linePath"', false)
        ->assertSee('setDashArray', false)
        ->assertSee('onMove($event)', false)
        ->assertSee('points[active].amount', false)
        // Fundraise Up-style axis + tooltip title.
        ->assertSee('MYR 250', false)
        ->assertSee('Total raised');
});

it('shows a status badge for each recent donation', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $donor = Donor::factory()->create();

    $succeeded = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => now(),
    ]);

    $failed = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Failed,
        'created_at' => now(),
    ]);

    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee(ucfirst($succeeded->status->value), false)
        ->assertSee('Succeeded', false)
        ->assertSee(ucfirst($failed->status->value), false)
        ->assertSee('Failed', false);
});

it('calculates donations by frequency for the selected period', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'created_at' => now(),
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
        'created_at' => now(),
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'created_at' => now()->subDay(),
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Failed,
        'type' => DonationType::OneTime,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', '7_days');

    $frequency = $component->instance()->donationsByFrequency();

    expect($frequency['one_time_total'])->toBe(2);
    expect($frequency['recurring_total'])->toBe(1);
    expect($frequency['max_scale'])->toBeGreaterThanOrEqual(3);
    expect(collect($frequency['days'])->sum('one_time'))->toBe(2);
    expect(collect($frequency['days'])->sum('recurring'))->toBe(1);
    expect(collect($frequency['days'])->sum('total'))->toBe(3);
    expect($frequency['donations_url'])->toBe(route('app.donations.index'));
    expect($frequency['days'][0]['date_from_key'])->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    expect($frequency['days'][0]['date_to_key'])->toBe($frequency['days'][0]['date_from_key']);
    expect($frequency['days'])->toHaveCount(7);
});

it('aggregates donations by frequency into weekly buckets for long periods', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'created_at' => now(),
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
        'created_at' => now()->subDays(60),
    ]);

    $frequency = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', '90_days')
        ->instance()
        ->donationsByFrequency();

    expect($frequency['days'])->toHaveCount(13);
    expect($frequency['one_time_total'])->toBe(1);
    expect($frequency['recurring_total'])->toBe(1);
    expect(collect($frequency['days'])->sum('total'))->toBe(2);

    $firstBucket = $frequency['days'][0];
    expect($firstBucket['date_from_key'])->toBe(now()->subDays(89)->format('Y-m-d'));
    expect($firstBucket['date_to_key'])->toBe(now()->subDays(83)->format('Y-m-d'));
});

it('has sidebar navigation', function () {
    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('Fundraise')
        ->assertSee('Finance')
        ->assertSee('Supporters')
        ->assertSee('Organization');
});

it('opens virtual terminal navigation in a new tab', function () {
    actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('href="/virtual-terminal"', false)
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
    $component->assertSeeHtml('wire:key="frequency-chart-today');

    $component->set('period', '7_days')->assertSet('period', '7_days');
    $component->assertSeeHtml('wire:key="frequency-chart-7_days');
    $component->assertSeeHtml('wire:key="payment-methods-chart-7_days');
    $component->assertSeeHtml(route('app.donations.index', ['period' => '7_days']));

    $component->set('period', 'this_month');
    $component->assertSeeHtml(route('app.donations.index', ['period' => 'this_month']));

    expect(substr_count($component->html(), 'aria-label="View donations for this period"'))->toBe(5);

    expect($component->donationTrend)->toBeArray();
    expect($component->campaignsBreakdown)->toBeArray();
    expect($component->donationSizes)->toBeArray();
    expect($component->paymentMethods)->toBeArray();
    expect($component->recentDonations)->toBeCollection();
});

it('groups payment methods with labels, counts, totals, and percentages', function () {
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->for($this->organization)->create();

    Donation::factory()->count(3)->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => null,
        'status' => DonationStatus::Succeeded,
        'payment_method_type' => 'card',
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => null,
        'status' => DonationStatus::Succeeded,
        'payment_method_type' => 'fpx',
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50.00,
        'base_amount' => null,
        'status' => DonationStatus::Succeeded,
        'payment_method_type' => null,
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 500.00,
        'base_amount' => null,
        'status' => DonationStatus::Pending,
        'payment_method_type' => 'card',
    ]);

    $component = Livewire::actingAs($this->user)->test(Dashboard::class);

    $component->assertSeeHtml('renderDonutChart')
        ->assertSeeHtml('renderStackedBarChart')
        ->assertSeeHtml('IntersectionObserver')
        ->assertSeeHtml("downloadCanvasChartPng('payment-methods-chart'")
        ->assertSeeHtml("downloadCanvasChartPng('frequency-chart'")
        ->assertSeeHtml("downloadTrendPng('Donation Trend'")
        ->assertSeeHtml("downloadBarRowsPng('Donations by Campaign'")
        ->assertSeeHtml("downloadBarRowsPng('Donation Sizes'")
        ->assertDontSeeHtml('@js(');

    $methods = $component->instance()->paymentMethods();

    expect($methods)->toHaveCount(3);

    expect($methods[0])->toMatchArray([
        'name' => 'Card',
        'count' => 3,
        'value' => 300.00,
        'percentage' => 67.0,
    ]);

    expect($methods[1])->toMatchArray([
        'name' => 'FPX',
        'count' => 1,
        'value' => 100.00,
        'percentage' => 22.0,
    ]);

    expect($methods[2])->toMatchArray([
        'name' => 'Other',
        'count' => 1,
        'value' => 50.00,
        'percentage' => 11.0,
    ]);
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
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('MRR')
        ->assertSee('At-risk Subscriptions')
        ->assertSee('Expected (30 days)');
});
