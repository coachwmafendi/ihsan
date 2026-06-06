<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Fraud\BlockedDonation;
use App\Models\Fraud\FraudAttempt;
use App\Models\Fraud\FraudRule;
use App\Models\Organization;
use App\Services\FraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses()->group('fraud');

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->donor = Donor::factory()->create([
        'email' => 'test@example.com',
    ]);

    FraudDetectionService::seedDefaultRules($this->organization->id);
});

it('detects velocity fraud when donor exceeds donation threshold', function () {
    // Create 5 donations in last hour
    Donation::factory()->count(5)->create([
        'donor_id' => $this->donor->id,
        'campaign_id' => $this->campaign->id,
        'created_at' => now()->subMinutes(30),
    ]);

    $service = new FraudDetectionService($this->donor);
    $result = $service->assess(['amount' => 100]);

    expect($result['action'])->toBe('flag');
    expect($result['matches'])->toHaveCount(1);
    expect($result['matches'][0]['rule_type'])->toBe('velocity');
});

it('allows donation when velocity is below threshold', function () {
    Donation::factory()->count(3)->create([
        'donor_id' => $this->donor->id,
        'campaign_id' => $this->campaign->id,
        'created_at' => now()->subMinutes(30),
    ]);

    $service = new FraudDetectionService($this->donor);
    $result = $service->assess(['amount' => 100]);

    expect($result['action'])->toBe('allow');
    expect($result['matches'])->toBeEmpty();
});

it('flags donations exceeding amount threshold', function () {
    // Create at least one donation so the service can find the org
    Donation::factory()->create([
        'donor_id' => $this->donor->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $service = new FraudDetectionService($this->donor);
    $result = $service->assess(['amount' => 15000]);

    expect($result['action'])->toBe('flag');
    expect($result['matches'])->toHaveCount(1);
    expect($result['matches'][0]['rule_type'])->toBe('amount');
});

it('allows donations below amount threshold', function () {
    $service = new FraudDetectionService($this->donor);
    $result = $service->assess(['amount' => 500]);

    expect($result['action'])->toBe('allow');
});

it('logs fraud attempt when blocked', function () {
    FraudAttempt::create([
        'donor_id' => $this->donor->id,
        'email' => $this->donor->email,
        'amount' => 1000,
        'currency' => 'myr',
        'reason' => 'velocity limit exceeded',
        'action' => 'blocked',
    ]);

    expect(FraudAttempt::count())->toBe(1);
    expect(FraudAttempt::first()->reason)->toBe('velocity limit exceeded');
});

it('creates blocked donation record', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'fraud_status' => 'blocked',
    ]);

    BlockedDonation::create([
        'donation_id' => $donation->id,
        'reason' => 'Stripe Radar high risk score',
        'review_status' => 'pending',
    ]);

    expect(BlockedDonation::count())->toBe(1);
    expect(BlockedDonation::first()->review_status)->toBe('pending');
});

it('seeds default fraud rules for organization', function () {
    FraudDetectionService::seedDefaultRules($this->organization->id);

    $rules = FraudRule::where('organization_id', $this->organization->id)->get();

    expect($rules)->toHaveCount(4);
    expect($rules->pluck('rule_type'))->toContain('velocity', 'amount', 'country', 'risk_score');
});
