<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->organization = Organization::factory()->withoutStripe()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

// Settings Profile
it('renders settings profile page', function () {
    actingAs($this->user)
        ->get('/app/settings/profile')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Profile');
});

it('redirects guests from settings profile', function () {
    get('/app/settings/profile')->assertRedirect('/login');
});

// Settings Payment
it('renders settings payment page', function () {
    actingAs($this->user)
        ->get('/app/settings/payment')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Payment');
});

// Settings Notifications
it('renders settings notifications page', function () {
    actingAs($this->user)
        ->get('/app/settings/notifications')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Notifications');
});

// Billing
it('renders billing page', function () {
    actingAs($this->user)
        ->get('/app/billing')
        ->assertOk()
        ->assertSee('Billing');
});

// Stripe Onboarding
it('renders stripe onboarding page', function () {
    actingAs($this->user)
        ->get('/app/stripe-onboarding')
        ->assertOk()
        ->assertSee('Stripe');
});
