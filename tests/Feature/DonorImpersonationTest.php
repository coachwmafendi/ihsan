<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

it('carries impersonation context in a signed magic-login url instead of the panel session', function () {
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
    $location = $response->headers->get('Location');

    expect($location)->toMatch('/donorportal\/[^\/]+\/login\/[A-Za-z0-9]+/');
    expect($location)->toContain('impersonate=1');
    expect($location)->toContain('signature=');
    expect($location)->toContain('return=');

    // The panel-domain session must not carry the impersonation keys directly;
    // they only get written once the signed url is followed on the root domain.
    expect(session()->has('admin_impersonating_donor_id'))->toBeFalse();

    $this->get($location)
        ->assertRedirect(route('donorportal.dashboard', $organization));

    expect(session('admin_impersonating_donor_id'))->toBe($donor->getKey());
    expect(session('admin_impersonating_donor_public_id'))->toBe($donor->public_id);
    expect(session('admin_impersonating_donor_name'))->toBe($donor->name);
    expect(session('donor_id'))->toBe($donor->getKey());
});

it('does not write impersonation keys when the magic-login signature is tampered with', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $response = $this->actingAs($user)
        ->post(route('admin.donor-portal.impersonate', $donor));

    $location = $response->headers->get('Location');

    $tamperedLocation = preg_replace('/signature=[^&]+/', 'signature=tampered', $location);

    // Donor login itself may still succeed (that's existing magic-link behavior);
    // only the impersonation context must be withheld.
    $this->get($tamperedLocation);

    expect(session()->has('admin_impersonating_donor_id'))->toBeFalse();
    expect(session()->has('admin_impersonating_donor_public_id'))->toBeFalse();
    expect(session()->has('admin_impersonating_donor_name'))->toBeFalse();
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
    $donor = Donor::factory()->create();
    $returnUrl = route('app.supporters.show', $donor);

    $this->withSession([
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

it('rejects exit requests when no impersonation is active in the session', function () {
    $this->post(route('admin.donor-portal.exit'))
        ->assertForbidden();
});

it('disables donor-only actions and shows tooltip when an admin is impersonating', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $donor = Donor::factory()->create();

    $response = $this->actingAs($user)
        ->withSession([
            'donor_id' => $donor->getKey(),
            'organization_id' => $organization->getKey(),
            'admin_impersonating_donor_id' => $donor->getKey(),
            'admin_impersonating_donor_public_id' => $donor->public_id,
            'admin_impersonating_donor_name' => $donor->name,
        ])
        ->get(route('donorportal.dashboard', $organization))
        ->assertOk();

    $tooltip = 'This feature is available to supporters only.';

    $response->assertSee($tooltip, false);
    $response->assertSee('Make a new donation', false);
    $response->assertSee('Report a problem', false);
    expect($response->getContent())->toMatch('/<button[^>]*disabled[^>]*>.*?Make a new donation/s');
    expect($response->getContent())->toMatch('/<button[^>]*disabled[^>]*>.*?Report a problem/s');
});

it('keeps donor-only actions enabled for a regular donor session', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $response = $this->withSession([
        'donor_id' => $donor->getKey(),
        'organization_id' => $organization->getKey(),
    ])
        ->get(route('donorportal.dashboard', $organization))
        ->assertOk();

    $tooltip = 'This feature is available to supporters only.';

    $response->assertDontSee($tooltip, false);
    expect($response->getContent())->not->toMatch('/<button[^>]*disabled[^>]*>.*?Make a new donation/s');
    expect($response->getContent())->not->toMatch('/<button[^>]*disabled[^>]*>.*?Report a problem/s');
});

it('allows impersonating a donor who only has a subscription to the organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Subscription::factory()->for($donor)->for($campaign)->create();

    $response = $this->actingAs($user)
        ->post(route('admin.donor-portal.impersonate', $donor))
        ->assertRedirect();

    $this->get($response->headers->get('Location'));

    expect(session('admin_impersonating_donor_id'))->toBe($donor->getKey());
});

it('sanitizes an external return url when exiting impersonation', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession([
        'admin_impersonating_donor_id' => $donor->getKey(),
        'admin_impersonating_donor_public_id' => $donor->public_id,
        'admin_impersonating_donor_name' => $donor->name,
        'admin_impersonate_return_url' => 'https://evil.com/phish',
        'donor_id' => $donor->getKey(),
        'organization_id' => $organization->getKey(),
    ])
        ->post(route('admin.donor-portal.exit'))
        ->assertRedirect(route('app.supporters.index'));
});

it('rejects a backslash-based open-redirect return url when exiting impersonation', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession([
        'admin_impersonating_donor_id' => $donor->getKey(),
        'admin_impersonating_donor_public_id' => $donor->public_id,
        'admin_impersonating_donor_name' => $donor->name,
        'admin_impersonate_return_url' => '/\\evil.com',
        'donor_id' => $donor->getKey(),
        'organization_id' => $organization->getKey(),
    ])
        ->post(route('admin.donor-portal.exit'))
        ->assertRedirect(route('app.supporters.index'));
});

it('rejects a protocol-relative open-redirect return url when exiting impersonation', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession([
        'admin_impersonating_donor_id' => $donor->getKey(),
        'admin_impersonating_donor_public_id' => $donor->public_id,
        'admin_impersonating_donor_name' => $donor->name,
        'admin_impersonate_return_url' => '//evil.com',
        'donor_id' => $donor->getKey(),
        'organization_id' => $organization->getKey(),
    ])
        ->post(route('admin.donor-portal.exit'))
        ->assertRedirect(route('app.supporters.index'));
});

it('rejects a userinfo-spoofed open-redirect return url when exiting impersonation', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession([
        'admin_impersonating_donor_id' => $donor->getKey(),
        'admin_impersonating_donor_public_id' => $donor->public_id,
        'admin_impersonating_donor_name' => $donor->name,
        'admin_impersonate_return_url' => 'https://app.example.test@evil.com/x',
        'donor_id' => $donor->getKey(),
        'organization_id' => $organization->getKey(),
    ])
        ->post(route('admin.donor-portal.exit'))
        ->assertRedirect(route('app.supporters.index'));
});
