<?php

use App\Actions\Chip\CreateRecurringPlan;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

it('throws exception for a non-recurring donation', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    app(CreateRecurringPlan::class)->create($donation, 'token123');
})->throws(InvalidArgumentException::class, 'Donation must be recurring to create a CHIP recurring plan.');

it('throws exception for a non-succeeded donation', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
    ]);

    app(CreateRecurringPlan::class)->create($donation, 'token123');
})->throws(InvalidArgumentException::class, 'Donation must be succeeded to create a CHIP recurring plan.');

it('creates a subscription from a chip recurring token and links the source donation', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'MYR',
        'gross_amount' => 50.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
        'source' => 'campaign_page',
    ]);

    $subscription = app(CreateRecurringPlan::class)->create($donation, 'token123');

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->chip_recurring_token)->toBe('token123')
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->interval)->toBe(SubscriptionInterval::Monthly)
        ->and((float) $subscription->amount)->toBe(50.00)
        ->and($subscription->currency)->toBe('MYR')
        ->and($subscription->campaign_id)->toBe($campaign->id)
        ->and($subscription->donor_id)->toBe($donor->id)
        ->and($subscription->source)->toBe('campaign_page');

    $donation = $donation->fresh();

    expect($donation->subscription_id)->toBe($subscription->id);
});

it('does not overwrite an existing subscription link on the source donation', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $existingSubscription = Subscription::factory()->for($campaign)->for($donor)->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'MYR',
        'gross_amount' => 50.00,
        'subscription_id' => $existingSubscription->id,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $subscription = app(CreateRecurringPlan::class)->create($donation, 'token123');

    expect($subscription->id)->not->toBe($existingSubscription->id);
    expect($donation->fresh()->subscription_id)->toBe($existingSubscription->id);
});
