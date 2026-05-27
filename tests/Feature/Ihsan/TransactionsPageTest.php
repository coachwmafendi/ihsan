<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;

it('shows all platform transactions to super admins', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get('/admin/transactions')
        ->assertSuccessful()
        ->assertSee('100.00');
});

it('shows transactions page accessible at /admin/transactions', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get('/admin/transactions')
        ->assertSuccessful();
});

it('denies transactions page to ngo admins', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);

    $this->actingAs($user)
        ->get('/admin/transactions')
        ->assertForbidden();
});

it('shows approximated MYR amount with tooltip for foreign currency donations', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50.00,
        'currency' => 'usd',
        'base_currency' => 'myr',
        'base_amount' => 235.50,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get('/admin/transactions')
        ->assertSuccessful()
        ->assertSee('≈ MYR 235.50')
        ->assertSee('USD 50.00'); // shown in tooltip
});

it('shows plain MYR amount for local currency donations', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 120.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get('/admin/transactions')
        ->assertSuccessful()
        ->assertSee('MYR 120.00')
        ->assertDontSee('≈');
});
