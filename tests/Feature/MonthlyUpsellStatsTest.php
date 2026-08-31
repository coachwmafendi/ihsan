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

it('withholds the rate until thirty donors have seen the offer', function () {
    foreach (range(1, 29) as $i) {
        Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
            'utm_params' => ['upsell_shown' => true],
        ]);
    }

    expect($this->stats->forCampaign($this->campaign)['shows_rate'])->toBeFalse();
});

it('reports the rate once thirty donors have seen the offer', function () {
    foreach (range(1, 30) as $i) {
        Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
            'utm_params' => ['upsell_shown' => true],
        ]);
    }

    expect($this->stats->forCampaign($this->campaign)['shows_rate'])->toBeTrue();
});

it('counts only the donors inside the selected window', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'created_at' => now()->subDays(5),
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'created_at' => now()->subDays(60),
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    expect($this->stats->forCampaign($this->campaign, 30)['offers_shown'])->toBe(1)
        ->and($this->stats->forCampaign($this->campaign, 90)['offers_shown'])->toBe(2)
        ->and($this->stats->forCampaign($this->campaign)['offers_shown'])->toBe(2);
});

it('excludes an older plan from the added monthly value inside a window', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($this->campaign)->for($donor)->create();

    Donation::factory()->for($this->campaign)->for($donor)->create([
        'created_at' => now()->subDays(120),
        'subscription_id' => $subscription->id,
        'status' => DonationStatus::Succeeded,
        'base_amount' => 60.00,
        'currency' => 'myr',
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    expect($this->stats->forCampaign($this->campaign, 30)['added_monthly_value'])->toBe(0.0)
        ->and($this->stats->forCampaign($this->campaign)['added_monthly_value'])->toBe(60.0);
});

it('splits acceptances by which offer the donor took', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true, 'upsell_offer_taken' => 'own_amount'],
    ]);

    Donation::factory()->count(2)->sequence(fn () => ['donor_id' => Donor::factory()])
        ->for($this->campaign)->create([
            'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true, 'upsell_offer_taken' => 'lighter'],
        ]);

    $result = $this->stats->forCampaign($this->campaign);

    expect($result['took_own_amount'])->toBe(1)
        ->and($result['took_lighter'])->toBe(2);
});

it('counts a donor once when a retried payment starts a single plan', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($this->campaign)->for($donor)->create();

    // A retry writes a second succeeded row against the same plan; counting
    // donations here would double both the plan and the money it adds.
    Donation::factory()->count(2)->for($this->campaign)->for($donor)->create([
        'subscription_id' => $subscription->id,
        'status' => DonationStatus::Succeeded,
        'base_amount' => 60.00,
        'currency' => 'myr',
        'utm_params' => ['upsell_shown' => true, 'upsell_accepted' => true],
    ]);

    $result = $this->stats->forCampaign($this->campaign);

    expect($result['plans_started'])->toBe(1)
        ->and($result['added_monthly_value'])->toBe(60.0);
});
