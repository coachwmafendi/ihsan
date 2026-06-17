<?php

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Livewire\App\Subscriptions\SubscriptionShow;
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

it('renders the subscription show page', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertStatus(200)
        ->assertSee('Recurring plan')
        ->assertSee($subscription->public_id);
});

it('shows the active recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'payment_count' => 3,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This monthly recurring donation is active.')
        ->assertSee('Installment #4')
        ->assertSee($subscription->current_period_end->format('M d, Y, g:i A'));
});

it('projects the next installment date when current period end is stale', function () {
    $staleDate = now()->subDay()->startOfDay();
    $expectedDate = $staleDate->copy()->addMonth();

    while ($expectedDate->isPast()) {
        $expectedDate->addMonth();
    }

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'payment_count' => 3,
        'current_period_end' => $staleDate,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This monthly recurring donation is active.')
        ->assertSee('Installment #4')
        ->assertSee($expectedDate->format('M d, Y, g:i A'));
});

it('shows the scheduled cancellation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'cancel_at_period_end' => true,
        'current_period_end' => now()->addWeek(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This monthly recurring donation is scheduled to cancel.')
        ->assertSee('The final installment will be charged on')
        ->assertSee($subscription->current_period_end->format('M d, Y, g:i A'));
});

it('shows the paused recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Paused,
        'interval' => SubscriptionInterval::Monthly,
        'paused_until' => now()->addWeeks(2),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This monthly recurring donation is paused.')
        ->assertSee('Installments will resume on')
        ->assertSee($subscription->paused_until->format('M d, Y, g:i A'));
});

it('shows the cancelled recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Cancelled,
        'interval' => SubscriptionInterval::Monthly,
        'cancelled_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This recurring donation was cancelled on')
        ->assertSee($subscription->cancelled_at->format('M d, Y, g:i A'))
        ->assertSee('No further charges will be made.');
});

it('shows the past due recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::PastDue,
        'interval' => SubscriptionInterval::Monthly,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('The most recent installment failed.')
        ->assertSee('We will retry the payment shortly.');
});

it('shows the incomplete recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Incomplete,
        'interval' => SubscriptionInterval::Monthly,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This recurring donation is incomplete.')
        ->assertSee('Please complete the payment setup.');
});

it('opens the edit payment details modal', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'amount' => 35.00,
        'currency' => 'sgd',
        'cover_fee' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openEditPaymentDetailsModal')
        ->assertSet('showEditPaymentDetailsModal', true)
        ->assertSet('editAmount', 35.00)
        ->assertSet('editInterval', 'monthly')
        ->assertSet('editCoverFee', true)
        ->assertSee('Edit payment details')
        ->assertSee('Installment amount')
        ->assertSee('Frequency');
});

it('validates payment details form', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openEditPaymentDetailsModal')
        ->set('editAmount', 0)
        ->call('savePaymentDetails')
        ->assertHasErrors(['editAmount']);
});

it('opens the skip installments modal and computes next installment date', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openSkipModal')
        ->assertSet('showSkipModal', true)
        ->assertSet('skipDuration', '1')
        ->assertSee('Skip installments')
        ->set('skipDuration', 'custom')
        ->set('customSkipMonths', 3)
        ->assertSee($subscription->current_period_end->copy()->addMonths(3)->format('M d, Y, g:i A'));
});

it('shows approximate myr total when subscription donations lack base amount', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'currency' => 'usd',
        'amount' => 50.00,
    ]);

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'base_amount' => null,
    ]);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'base_amount' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('≈ MYR 100.00');
});
