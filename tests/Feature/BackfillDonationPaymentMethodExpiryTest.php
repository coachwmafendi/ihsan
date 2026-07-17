<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('dry run reports donations that need expiry backfill', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_test_123',
        'payment_method_brand' => 'visa',
        'payment_method_last4' => '4242',
        'payment_method_exp_month' => null,
        'payment_method_exp_year' => null,
    ]);

    $this->artisan('app:backfill-donation-payment-method-expiry', ['--dry-run' => true])
        ->expectsOutputToContain('Backfilling expiry for 1 donation(s)...')
        ->expectsOutputToContain('[dry-run] Would backfill donation')
        ->assertSuccessful();
});

it('skips donations that already have expiry', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_test_123',
        'payment_method_brand' => 'visa',
        'payment_method_last4' => '4242',
        'payment_method_exp_month' => 12,
        'payment_method_exp_year' => 2025,
    ]);

    $this->artisan('app:backfill-donation-payment-method-expiry')
        ->expectsOutput('No donations need expiry backfill.')
        ->assertSuccessful();
});

it('respects the limit option', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->count(5)->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => fn () => 'pi_test_'.fake()->unique()->bothify('???###'),
        'payment_method_brand' => 'visa',
        'payment_method_last4' => '4242',
    ]);

    $this->artisan('app:backfill-donation-payment-method-expiry', ['--dry-run' => true, '--limit' => 2])
        ->expectsOutputToContain('Backfilling expiry for 2 donation(s)...')
        ->assertSuccessful();
});
