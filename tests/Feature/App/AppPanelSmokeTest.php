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
        'https://app.example.test/',
        'https://app.example.test/dashboard',
        'https://app.example.test/campaigns',
        'https://app.example.test/campaigns/create',
        'https://app.example.test/campaigns/'.$campaign->public_id.'/edit',
        'https://app.example.test/donations',
        'https://app.example.test/donations/'.$donation->public_id,
        'https://app.example.test/subscriptions',
        'https://app.example.test/subscriptions/'.$subscription->public_id,
        'https://app.example.test/recurring-plans',
        'https://app.example.test/supporters',
        'https://app.example.test/supporters/'.$donor->public_id,
        'https://app.example.test/elements',
        'https://app.example.test/elements/create',
        'https://app.example.test/elements/'.$element->public_id.'/edit',
        'https://app.example.test/notifications',
        'https://app.example.test/audit-log',
        'https://app.example.test/billing',
        'https://app.example.test/payouts',
        'https://app.example.test/insights',
        'https://app.example.test/members',
        'https://app.example.test/teams',
        'https://app.example.test/virtual-terminal',
        'https://app.example.test/stripe-onboarding',
        'https://app.example.test/settings/organization',
        'https://app.example.test/settings/payment',
        'https://app.example.test/settings/notifications',
        'https://app.example.test/settings/account',
        'https://app.example.test/settings/allow-domains',
        'https://app.example.test/settings/donor-portal',
        'https://app.example.test/settings/installation',
        'https://app.example.test/settings/tracking',
        'https://app.example.test/developer/api-keys',
        'https://app.example.test/developer/embed-forms',
        'https://app.example.test/developer/webhooks',
    ];

    foreach ($paths as $path) {
        $this->followingRedirects()->get($path)->assertOk();
    }
});

it('does not hardcode the documentation url in the sidebar component', function () {
    $contents = file_get_contents(resource_path('views/components/sidebar.blade.php'));

    expect($contents)->not->toContain('ihsan.test:8443/docs')
        ->and($contents)->toContain("route('docs.show')");
});
