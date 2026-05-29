<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\App\Pages\Insights;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

it('calculates ngo insights from organization-scoped records', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);
    $donor = Donor::factory()->create();

    $campaign = Campaign::factory()->for($organization)->create();
    $otherCampaign = Campaign::factory()->for($otherOrganization)->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 30.00,
        'base_amount' => 30.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 10.00,
        'base_amount' => 10.00,
        'status' => DonationStatus::Failed,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($otherCampaign)->for($donor)->create([
        'gross_amount' => 999.00,
        'base_amount' => 999.00,
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
        ->assertSet('totalRaised', '130.00')
        ->assertSet('monthlyRecurringRevenue', '30.00')
        ->assertSet('activeRecurringDonors', 1)
        ->assertSet('oneTimeDonationsTotal', '100.00')
        ->assertSet('firstInstallmentsTotal', '30.00')
        ->assertSet('successfulDonationsCount', 2)
        ->assertSee('Performance')
        ->assertSee('Recurring revenue')
        ->assertSee('Payment methods')
        ->assertSee('One-time donations')
        ->assertSee('First installments');
});

it('switches tabs and exposes data arrays', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 30.00,
        'status' => SubscriptionStatus::Active,
        'interval' => 'monthly',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Insights::class);

    // Default tab
    $component->assertSet('activeTab', 'overview');

    // Tab switching
    $component->call('setActiveTab', 'performance')->assertSet('activeTab', 'performance');
    $component->call('setActiveTab', 'recurring-plans')->assertSet('activeTab', 'recurring-plans');
    $component->call('setActiveTab', 'recurring-revenue')->assertSet('activeTab', 'recurring-revenue');
    $component->call('setActiveTab', 'retention')->assertSet('activeTab', 'retention');
    $component->call('setActiveTab', 'payment-methods')->assertSet('activeTab', 'payment-methods');
    $component->call('setActiveTab', 'frequencies')->assertSet('activeTab', 'frequencies');
    $component->call('setActiveTab', 'elements')->assertSet('activeTab', 'elements');
    $component->call('setActiveTab', 'url')->assertSet('activeTab', 'url');

    // Verify data arrays are populated
    expect($component->monthlyRevenue)->toBeArray();
    expect($component->campaignPerformance)->toBeArray();
    expect($component->subscriptionStatusDistribution)->toBeArray();
    expect($component->subscriptionIntervalBreakdown)->toBeArray();
    expect($component->retentionOverview)->toBeArray();
    expect($component->paymentBrandBreakdown)->toBeArray();
    expect($component->paymentTypeBreakdown)->toBeArray();
    expect($component->elementsList)->toBeArray();
    expect($component->campaignUrlPerformance)->toBeArray();

    // Verify specific computed values
    expect($component->mrrOverview['currentMrr'])->toBeString();
    expect($component->mrrOverview['newThisMonth'])->toBeInt();
    expect($component->mrrOverview['activeCount'])->toBeInt();
    expect($component->retentionOverview['totalDonors'])->toBe(1);
    expect($component->retentionOverview['repeatDonors'])->toBe(0);
});
