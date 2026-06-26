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
