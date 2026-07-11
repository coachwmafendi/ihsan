<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

it('loads all major app panel pages for an organization admin', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'status' => SubscriptionStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create();

    $this->actingAs($user);

    $paths = [
        '/app',
        '/app/dashboard',
        '/app/campaigns',
        '/app/campaigns/create',
        '/app/campaigns/'.$campaign->public_id.'/edit',
        '/app/donations',
        '/app/donations/'.$donation->public_id,
        '/app/subscriptions',
        '/app/subscriptions/'.$subscription->public_id,
        '/app/recurring-plans',
        '/app/supporters',
        '/app/supporters/'.$donor->public_id,
        '/app/elements',
        '/app/elements/create',
        '/app/elements/'.$element->public_id.'/edit',
        '/app/notifications',
        '/app/audit-log',
        '/app/billing',
        '/app/payouts',
        '/app/insights',
        '/app/members',
        '/app/teams',
        '/app/virtual-terminal',
        '/app/stripe-onboarding',
        '/app/settings/organization',
        '/app/settings/payment',
        '/app/settings/notifications',
        '/app/settings/account',
        '/app/settings/allow-domains',
        '/app/settings/donor-portal',
        '/app/settings/installation',
        '/app/settings/tracking',
        '/app/developer/api-keys',
        '/app/developer/embed-forms',
        '/app/developer/webhooks',
    ];

    foreach ($paths as $path) {
        $this->followingRedirects()->get($path)->assertOk();
    }
});
