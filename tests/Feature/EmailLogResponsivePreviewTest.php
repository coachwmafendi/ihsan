<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\User;

it('shows responsive preview for an authorized email log', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();
    $log = DonorEmailLog::factory()->donation($donation)->create();

    $this->actingAs($user)
        ->get(route('app.supporters.emails.responsive-preview', ['donor' => $donor->public_id, 'emailLog' => $log->public_id]))
        ->assertOk()
        ->assertSee($log->subject)
        ->assertSeeHtml('aria-label="Mobile preview"')
        ->assertSeeHtml('aria-label="Tablet preview"')
        ->assertSeeHtml('aria-label="Desktop preview"')
        ->assertSeeHtml('<iframe');
});

it('blocks the responsive preview for another organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($otherOrganization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();
    $log = DonorEmailLog::factory()->donation($donation)->create();

    $this->actingAs($user)
        ->get(route('app.supporters.emails.responsive-preview', ['donor' => $donor->public_id, 'emailLog' => $log->public_id]))
        ->assertNotFound();
});

it('blocks the responsive preview when the email does not belong to the donor', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    [$donor, $otherDonor] = Donor::factory()->count(2)->create();
    $donation = Donation::factory()->for($otherDonor)->for($campaign)->create();
    $log = DonorEmailLog::factory()->donation($donation)->create();

    $this->actingAs($user)
        ->get(route('app.supporters.emails.responsive-preview', ['donor' => $donor->public_id, 'emailLog' => $log->public_id]))
        ->assertNotFound();
});

it('redirects guests to login', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();
    $log = DonorEmailLog::factory()->donation($donation)->create();

    $this->get(route('app.supporters.emails.responsive-preview', ['donor' => $donor->public_id, 'emailLog' => $log->public_id]))
        ->assertRedirect(route('login'));
});
