<?php

use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

it('connects users to their organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    expect($user->organization)->toBeInstanceOf(Organization::class);
    expect($organization->users)->toHaveCount(1);
});

it('scopes donors through organization campaigns', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $campaign = Campaign::factory()->for($organization)->create();
    $otherCampaign = Campaign::factory()->for($otherOrganization)->create();

    $donor = Donor::factory()->create(['email' => 'same@example.test']);

    Donation::factory()->for($campaign)->for($donor)->create();
    Donation::factory()->for($otherCampaign)->for($donor)->create();
    Subscription::factory()->for($campaign)->for($donor)->create();

    expect($organization->campaigns)->toHaveCount(1);
    expect($campaign->donations)->toHaveCount(1);
    expect($campaign->subscriptions)->toHaveCount(1);
});
