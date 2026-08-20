<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Enums\UserRole;
use App\Http\Controllers\DonorNotificationController;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;

use function Pest\Laravel\actingAs;

const GTM_ID = 'GTM-TESTID1';

beforeEach(function () {
    config(['services.google.gtm_id' => GTM_ID]);
});

it('loads the container on the marketing site', function () {
    $this->get('https://example.test/')
        ->assertOk()
        ->assertSee('googletagmanager.com/gtm.js', false)
        ->assertSee(GTM_ID, false)
        ->assertSee('googletagmanager.com/ns.html?id='.GTM_ID, false);
});

it('loads the container on the admin panel', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);

    actingAs($user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->assertSee('googletagmanager.com/gtm.js', false)
        ->assertSee(GTM_ID, false);
});

it('loads the container on the admin login page', function () {
    $this->get('https://app.example.test/login')
        ->assertOk()
        ->assertSee('googletagmanager.com/gtm.js', false);
});

/**
 * The exclusions matter more than the inclusions: donation pages already carry
 * each NGO's own pixels, and those donors are the NGO's, not ours.
 */
it('stays off the donation form', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Active]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtm.js', false)
        ->assertDontSee(GTM_ID, false);
});

it('stays off donor-facing notification settings', function () {
    $donor = Donor::factory()->create();

    // Reached from an email, so the link is signed rather than session backed.
    $this->get(DonorNotificationController::unsubscribeUrl($donor))
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtm.js', false);
});

it('renders nothing at all when no container is configured', function () {
    config(['services.google.gtm_id' => null]);

    $this->get('https://example.test/')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});

/**
 * Cloudflare Web Analytics sets no cookies and cannot identify anyone, so it
 * runs everywhere — including the donor-facing pages the tag manager avoids.
 */
it('loads cloudflare analytics on our own pages', function () {
    config(['services.cloudflare.analytics_token' => 'cf-test-token']);

    $this->get('https://example.test/')
        ->assertOk()
        ->assertSee('static.cloudflareinsights.com/beacon.min.js', false)
        ->assertSee('cf-test-token', false);
});

it('loads cloudflare analytics on the donation form too', function () {
    config(['services.cloudflare.analytics_token' => 'cf-test-token']);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Active]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('static.cloudflareinsights.com/beacon.min.js', false)
        // Still no tag manager there, though.
        ->assertDontSee('googletagmanager.com/gtm.js', false);
});

it('renders no cloudflare beacon without a token', function () {
    config(['services.cloudflare.analytics_token' => null]);

    $this->get('https://example.test/')
        ->assertOk()
        ->assertDontSee('cloudflareinsights.com', false);
});
