<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\SubscriptionActivityLogger;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donor = Donor::factory()->create();
    $this->subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
    ]);
    $this->installment = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $this->subscription->id,
        'gross_amount' => 25.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);
});

it('logs subscription created activity', function () {
    SubscriptionActivityLogger::created($this->subscription, null, ['source' => 'webhook']);

    $activity = $this->subscription->activitiesAsSubject()->where('event', 'subscription.created')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('subscription')
        ->and($activity->properties['source'])->toBe('webhook');
});

it('logs subscription cancelled', function () {
    $this->subscription->update(['status' => SubscriptionStatus::Cancelled]);

    SubscriptionActivityLogger::cancelled($this->subscription, 'Customer request', null, ['source' => 'manual']);

    $activity = $this->subscription->activitiesAsSubject()->where('event', 'subscription.cancelled')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['reason'])->toBe('Customer request')
        ->and($activity->properties['to_status'])->toBe('cancelled');
});

it('logs installment created and charged', function () {
    SubscriptionActivityLogger::installmentCreated($this->subscription, $this->installment, null, ['source' => 'system_automated']);

    $created = $this->installment->activitiesAsSubject()->where('event', 'installment.created')->first();

    expect($created)->not->toBeNull()
        ->and($created->properties['installment_public_id'])->toBe($this->installment->public_id);

    SubscriptionActivityLogger::installmentCharged(
        $this->subscription,
        $this->installment,
        'stripe',
        'pi_123',
        null,
        ['source' => 'system_automated']
    );

    $charged = $this->installment->activitiesAsSubject()->where('event', 'installment.charged')->first();

    expect($charged)->not->toBeNull()
        ->and($charged->properties['processor'])->toBe('stripe')
        ->and($charged->properties['transaction_id'])->toBe('pi_123');
});
