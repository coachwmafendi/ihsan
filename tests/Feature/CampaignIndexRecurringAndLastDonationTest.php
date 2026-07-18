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
    Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'amount' => 50.00,
        'currency' => 'usd',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($this->user)
        ->test(CampaignIndex::class)
        ->assertSeeText('≈ MYR 50.00/mo');
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
        ->assertSeeText(now()->subDay()->format('M d, Y'));
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
        ->assertDontSeeText(now()->subDay()->format('M d, Y'))
        ->assertSeeText(now()->subDays(3)->format('M d, Y'));
});
