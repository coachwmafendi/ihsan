<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;

it('allows an ngo admin to start impersonating a donor', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $response = $this->actingAs($user)
        ->post(route('admin.donor-portal.impersonate', $donor));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toMatch('/donorportal\/[^\/]+\/login\/[A-Za-z0-9]+/');

    expect(session('admin_impersonating_donor_id'))->toBe($donor->getKey());
    expect(session('admin_impersonating_donor_public_id'))->toBe($donor->public_id);
    expect(session('admin_impersonating_donor_name'))->toBe($donor->name);
});

it('prevents non-admin users from impersonating a donor', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::SuperAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->post(route('admin.donor-portal.impersonate', $donor))
        ->assertForbidden();
});

it('prevents impersonating a donor from another organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($otherOrganization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->post(route('admin.donor-portal.impersonate', $donor))
        ->assertForbidden();
});

it('shows the impersonation bar on the donor portal when an admin is impersonating', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $donor = Donor::factory()->create();

    $this->actingAs($user)
        ->withSession([
            'donor_id' => $donor->getKey(),
            'organization_id' => $organization->getKey(),
            'admin_impersonating_donor_id' => $donor->getKey(),
            'admin_impersonating_donor_public_id' => $donor->public_id,
            'admin_impersonating_donor_name' => $donor->name,
        ])
        ->get(route('donorportal.dashboard', $organization))
        ->assertOk()
        ->assertSee('Viewing customer account:')
        ->assertSee($donor->name)
        ->assertSee('Exit Customer View');
});

it('hides the impersonation bar for regular donor sessions', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $donor = Donor::factory()->create();

    $this->actingAs($user)
        ->withSession([
            'donor_id' => $donor->getKey(),
            'organization_id' => $organization->getKey(),
        ])
        ->get(route('donorportal.dashboard', $organization))
        ->assertOk()
        ->assertDontSee('Viewing customer account:');
});

it('exits impersonation and returns to the configured return url', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $donor = Donor::factory()->create();
    $returnUrl = route('app.supporters.show', $donor);

    $this->actingAs($user)
        ->withSession([
            'admin_impersonating_donor_id' => $donor->getKey(),
            'admin_impersonating_donor_public_id' => $donor->public_id,
            'admin_impersonating_donor_name' => $donor->name,
            'admin_impersonate_return_url' => $returnUrl,
            'donor_id' => $donor->getKey(),
            'organization_id' => $organization->getKey(),
        ])
        ->post(route('admin.donor-portal.exit'))
        ->assertRedirect($returnUrl);

    expect(session()->has('admin_impersonating_donor_id'))->toBeFalse();
    expect(session()->has('donor_id'))->toBeFalse();
    expect(session()->has('organization_id'))->toBeFalse();
});
