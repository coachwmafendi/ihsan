<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignIndex;
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
    // The table renders dates in MYT, so freeze the clock past MYT midnight to
    // keep the UTC and MYT calendar dates apart.
    $this->travelTo('2026-08-20 17:00:00');
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'collected_amount' => 0,
    ]);
    $this->donor = Donor::factory()->create();
});

it('shows recurring columns with zero values when campaign has no subscriptions', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSee('Recurring')
        ->assertSee('Recurring amount')
        ->assertSeeText('MYR 0.00/mo');
});

it('shows active subscription count and monthly recurring amount', function () {
    Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 120.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Yearly,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSeeText('MYR 60.00/mo');
});

it('uses approximation symbol for recurring amount with non-myr subscriptions', function () {
    // This used to assert USD 50 appearing as MYR 50 - the bug itself, written
    // down as the expected answer. It is the converted figure that is marked
    // approximate, not the raw one relabelled.
    $plan = Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 50.00,
        'currency' => 'usd',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'subscription_id' => $plan->getKey(),
        'status' => DonationStatus::Succeeded,
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'base_amount' => 210.00,
        'exchange_rate' => 4.20,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSeeText('≈ MYR 210.00/mo')
        ->assertDontSeeText('MYR 50.00/mo');
});

it('ignores inactive subscriptions for recurring columns', function () {
    Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 75.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Cancelled,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertDontSeeText('MYR 125.00/mo')
        ->assertSeeText('MYR 50.00/mo');
});

it('shows the latest donation date in the last donation column', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subDays(5),
    ]);

    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSee('Last Donation')
        ->assertSeeText(myrTime(now()->subDay(), withLabel: false, format: 'M d, Y'));
});

it('shows dash when campaign has no donations', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSee('Last Donation')
        ->assertSeeText('—');
});

it('ignores non-succeeded donations when determining last donation', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Pending,
        'created_at' => now()->subDay(),
    ]);

    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subDays(3),
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertDontSeeText(myrTime(now()->subDay(), withLabel: false, format: 'M d, Y'))
        ->assertSeeText(myrTime(now()->subDays(3), withLabel: false, format: 'M d, Y'));
});

it('converts a foreign plan to ringgit instead of adding the two together', function () {
    // Six SGD plans worth about RM749 a month were reported as RM235: the list
    // summed the plan amounts whatever currency they were written in, then put
    // MYR in front of the total.
    $ringgitPlan = Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 35.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    $singaporePlan = Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 50.00,
        'currency' => 'sgd',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    // The rate the plan's own charge settled at - the only one we can prove.
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'subscription_id' => $singaporePlan->getKey(),
        'status' => DonationStatus::Succeeded,
        'currency' => 'sgd',
        'gross_amount' => 50.00,
        'base_amount' => 160.00,
        'exchange_rate' => 3.20,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        // 35 + (50 x 3.20) = 195.00, not 85.00.
        ->assertSeeText('MYR 195.00/mo')
        ->assertDontSeeText('MYR 85.00/mo');

    expect($ringgitPlan->fresh()->monthlyReportAmount())->toBe(35.0);
});

it('leaves a foreign plan out of the total when no charge has proved a rate', function () {
    // Better a figure short by one plan than a total built on a rate we invented.
    Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 40.00,
        'currency' => 'usd',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSeeText('MYR 0.00/mo')
        // The count still says a plan is there, and the sign says the money is not exact.
        ->assertSeeText('≈');
});
