<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Livewire\App\Donations\DonationShow;
use App\Livewire\App\Subscriptions\SubscriptionShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DonationActivityLogger;
use App\Services\SubscriptionActivityLogger;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donor = Donor::factory()->create();
    $this->donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 150.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);
});

it('shows the activity card and its section nav item on the donation page', function () {
    DonationActivityLogger::created($this->donation, null, ['source' => 'checkout_modal']);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Activity')
        ->assertSee("scrollToSection('section-activity')", false)
        ->assertSee('donation '.$this->donation->public_id.' created.')
        ->assertSee('Checkout modal');
});

it('reads a donation timeline oldest first', function () {
    DonationActivityLogger::created($this->donation);
    $this->travel(1)->minutes();
    DonationActivityLogger::succeeded($this->donation);

    $activities = Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->instance()
        ->activities;

    $events = $activities->pluck('event')->all();

    expect($events)->toContain('donation.created', 'donation.succeeded')
        ->and(array_search('donation.created', $events, true))
        ->toBeLessThan(array_search('donation.succeeded', $events, true));
});

it('keeps processing steps behind a toggle but always shows the failure', function () {
    DonationActivityLogger::created($this->donation);
    DonationActivityLogger::transactionAttemptInitiated($this->donation, 'stripe', 'pi_123');
    DonationActivityLogger::failed($this->donation, 'Your card was declined.');

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('processing step')
        ->assertSee('Your card was declined.');
});

it('links to the audit log once a donation outgrows the card', function () {
    foreach (range(1, 16) as $ignored) {
        DonationActivityLogger::created($this->donation);
    }

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('View full history in Audit Log')
        ->assertSee(route('app.audit-log.index', ['search' => $this->donation->public_id]), false);
});

it('caps the donation timeline at the newest entries', function () {
    foreach (range(1, 16) as $ignored) {
        DonationActivityLogger::created($this->donation);
    }

    $activities = Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->instance()
        ->activities;

    expect($activities)->toHaveCount(15);
});

it('shows only plan lifecycle events on the recurring plan page', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
    ]);
    $installment = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'gross_amount' => 25.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    SubscriptionActivityLogger::created($subscription);
    SubscriptionActivityLogger::installmentCharged($subscription, $installment, 'stripe', 'pi_123');

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('Recurring plan '.$subscription->public_id.' created.')
        ->assertDontSee('installment '.$installment->public_id.' of recurring plan')
        ->assertSee('Show installment events')
        ->call('toggleInstallmentEvents')
        ->assertSee('installment '.$installment->public_id.' of recurring plan')
        ->assertSee('Hide installment events');
});

it('renders the audit log page through the shared timeline', function () {
    DonationActivityLogger::created($this->donation, null, ['source' => 'checkout_modal']);

    $this->actingAs($this->user)
        ->get(route('app.audit-log.index'))
        ->assertOk()
        ->assertSee('donation '.$this->donation->public_id.' created.')
        ->assertSee('Donation');
});
