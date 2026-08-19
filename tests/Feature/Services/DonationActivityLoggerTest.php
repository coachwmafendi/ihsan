<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use App\Services\DonationActivityLogger;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donor = Donor::factory()->create();
    $this->donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 50.00,
        'currency' => 'myr',
    ]);
});

it('logs donation created activity', function () {
    DonationActivityLogger::created($this->donation, null, ['initiator' => 'donor', 'source' => 'donor_portal']);

    $activity = $this->donation->activitiesAsSubject()->where('event', 'donation.created')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('donation')
        ->and($activity->properties['initiator'])->toBe('donor')
        ->and($activity->properties['source'])->toBe('donor_portal');
});

it('logs payment processing initiated', function () {
    DonationActivityLogger::processingInitiated($this->donation, 'stripe', 'pi_123');

    $activity = $this->donation->activitiesAsSubject()->where('event', 'payment_processing_initiated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['processor'])->toBe('stripe')
        ->and($activity->properties['transaction_id'])->toBe('pi_123');
});

it('logs transaction attempt succeeded', function () {
    DonationActivityLogger::transactionAttemptSucceeded($this->donation, 'stripe', 'pi_123', 1);

    $activity = $this->donation->activitiesAsSubject()->where('event', 'transaction_attempt_succeeded')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['attempt'])->toBe(1);
});

it('logs transaction attempt failed', function () {
    DonationActivityLogger::transactionAttemptFailed($this->donation, 'stripe', 'pi_123', 'Card declined', 2);

    $activity = $this->donation->activitiesAsSubject()->where('event', 'transaction_attempt_failed')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['reason'])->toBe('Card declined')
        ->and($activity->properties['attempt'])->toBe(2);
});

it('logs donation refunded with admin causer', function () {
    $admin = User::factory()->for($this->organization)->create();
    Auth::login($admin);

    DonationActivityLogger::refunded($this->donation, 50.00, $admin, ['source' => 'manual']);

    $activity = $this->donation->activitiesAsSubject()->where('event', 'donation.refunded')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties['refund_amount'])->toEqual(50.0)
        ->and($activity->properties['source'])->toBe('manual');
});
