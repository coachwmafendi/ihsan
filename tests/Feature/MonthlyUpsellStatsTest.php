<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\MonthlyUpsellStats;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'allow_recurring' => true,
    ]);
    $this->stats = app(MonthlyUpsellStats::class);
});

it('counts a donor who saw the offer and declined it', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => false],
    ]);

    $result = $this->stats->forCampaign($this->campaign);

    expect($result['offers_shown'])->toBe(1)
        ->and($result['accepted'])->toBe(0)
        ->and($result['plans_started'])->toBe(0)
        ->and($result['added_monthly_value'])->toBe(0.0);
});

it('counts one donor when a failed payment is retried several times', function () {
    $donor = Donor::factory()->create();

    Donation::factory()->count(4)->for($this->campaign)->for($donor)->create([
        'status' => DonationStatus::Failed,
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => false],
    ]);

    expect($this->stats->forCampaign($this->campaign)['offers_shown'])->toBe(1);
});

it('ignores donations that never carried the offer', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'utm_params' => ['source' => 'direct'],
    ]);

    expect($this->stats->forCampaign($this->campaign)['offers_shown'])->toBe(0);
});

it('ignores donations belonging to another campaign', function () {
    $other = Campaign::factory()->for($this->organization)->create();

    Donation::factory()->for($other)->for(Donor::factory())->create([
        'utm_params' => ['upsell_shown' => true],
    ]);

    expect($this->stats->forCampaign($this->campaign)['offers_shown'])->toBe(0);
});

it('counts a donor who accepted the offer', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    $result = $this->stats->forCampaign($this->campaign);

    expect($result['offers_shown'])->toBe(1)
        ->and($result['accepted'])->toBe(1);
});

it('counts an acceptance whose payment then failed', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Failed,
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    $result = $this->stats->forCampaign($this->campaign);

    expect($result['accepted'])->toBe(1)
        ->and($result['plans_started'])->toBe(0);
});

it('counts a started plan and the monthly value it adds', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($this->campaign)->for($donor)->create();

    Donation::factory()->for($this->campaign)->for($donor)->create([
        'subscription_id' => $subscription->id,
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 60.00,
        'base_amount' => 60.00,
        'currency' => 'myr',
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    $result = $this->stats->forCampaign($this->campaign);

    expect($result['plans_started'])->toBe(1)
        ->and($result['added_monthly_value'])->toBe(60.0)
        ->and($result['is_approximate'])->toBeFalse();
});

it('flags the added value as approximate when a plan is not in myr', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($this->campaign)->for($donor)->create();

    Donation::factory()->for($this->campaign)->for($donor)->create([
        'subscription_id' => $subscription->id,
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 20.00,
        'base_amount' => 94.50,
        'currency' => 'usd',
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    $result = $this->stats->forCampaign($this->campaign);

    expect($result['added_monthly_value'])->toBe(94.5)
        ->and($result['is_approximate'])->toBeTrue();
});

it('falls back to the gross amount when no base amount was stored', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($this->campaign)->for($donor)->create();

    Donation::factory()->for($this->campaign)->for($donor)->create([
        'subscription_id' => $subscription->id,
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 45.00,
        'base_amount' => null,
        'currency' => 'myr',
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    expect($this->stats->forCampaign($this->campaign)['added_monthly_value'])->toBe(45.0);
});
