<?php

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Livewire\App\Subscriptions\SubscriptionIndex;
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
    $this->donor = Donor::factory()->create();
});

it('renders the recurring plans index with a status column', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Status')
        ->assertSee('Active');
});

it('shows next installment tooltip on the status label', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Next installment:');
});

it('redirects to the subscription show page via navigate when a row is clicked', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->call('redirectToShow', $subscription->public_id)
        ->assertRedirect(route('app.subscriptions.show', $subscription));
});

it('displays expected monthly total from active and past due subscriptions', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 100.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 1200.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Yearly,
        'status' => SubscriptionStatus::Active,
    ]);

    $usdSubscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 10.00,
        'currency' => 'usd',
        'interval' => SubscriptionInterval::Weekly,
        'status' => SubscriptionStatus::Active,
    ]);

    Donation::factory()->create([
        'subscription_id' => $usdSubscription->id,
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'currency' => 'usd',
        'exchange_rate' => 4.5,
    ]);

    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 999.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Cancelled,
    ]);

    // Expected: 100 + (1200 / 12) + (10 * 52/12 * 4.5) = 395.00
    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Expected Monthly Total')
        ->assertSee('MYR 395.00');
});

it('includes new intervals in expected monthly total calculation', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 100.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Biweekly,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 300.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Quarterly,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 600.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Semiannual,
        'status' => SubscriptionStatus::PastDue,
    ]);

    // Expected: (100 * 26/12) + (300 / 3) + (600 / 6) = 216.67 + 100 + 100 = 416.67
    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Expected Monthly Total')
        ->assertSee('MYR 416.67');
});

it('displays status icon and label for each status variant', function () {
    foreach (SubscriptionStatus::cases() as $status) {
        Subscription::factory()->create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'status' => $status,
        ]);
    }

    $response = Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200);

    foreach (SubscriptionStatus::cases() as $status) {
        $response->assertSee($status->getLabel());
    }
});

it('shows scheduled to cancel label in the status column', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'cancel_at_period_end' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Scheduled to cancel');
});

it('displays active plans count with paused and past due breakdown', function () {
    Subscription::factory()->count(3)->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Paused,
    ]);

    Subscription::factory()->count(2)->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::PastDue,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Active Plans')
        ->assertSee('1 paused · 2 past due');
});

it('displays total collected from recurring installments', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Donation::factory()->create([
        'subscription_id' => $subscription->id,
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'base_amount' => 100.00,
    ]);

    Donation::factory()->create([
        'subscription_id' => $subscription->id,
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'base_amount' => 250.50,
    ]);

    // Non-subscription donation must not count.
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'base_amount' => 999.00,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Total Collected')
        ->assertSee('MYR 350.50');
});

it('marks expected monthly and total collected as approximate when non-MYR plans exist', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 10.00,
        'currency' => 'usd',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'currency' => 'usd',
        'gross_amount' => 10.00,
        'base_amount' => 45.00,
        'exchange_rate' => 4.5,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSeeInOrder(['Expected Monthly Total', '≈ MYR 45.00'])
        ->assertSeeInOrder(['Total Collected', '≈ MYR 45.00']);
});

it('shows new plans this month trend on the total plans card', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'created_at' => now()->subMonths(2),
    ]);

    Subscription::factory()->count(2)->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'created_at' => now()->startOfMonth()->addDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('+2 this month');
});

it('excludes scheduled to cancel plans from expected monthly total', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 200.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
        'cancel_at_period_end' => false,
    ]);

    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'amount' => 100.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
        'cancel_at_period_end' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertSee('MYR 200.00')
        ->assertDontSee('MYR 300.00');
});
