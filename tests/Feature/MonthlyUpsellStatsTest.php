<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
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
