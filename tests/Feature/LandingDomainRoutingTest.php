<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;

it('serves the landing page on the root domain', function () {
    $this->get('http://example.test/')->assertOk()->assertViewIs('welcome');
});

it('redirects the app panel domain root to the dashboard', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user)
        ->get('http://app.example.test/')
        ->assertRedirect(route('app.dashboard'));
});

it('serves the landing page on hosts without a domain-scoped route', function () {
    $this->get('/')->assertOk()->assertViewIs('welcome');
});

it('labels the landing page auth link "Sign in" to match the auth screens', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Sign in')
        ->assertDontSee('Log In');
});

it('loads a single Alpine instance on the landing page', function () {
    // Livewire tracks asset injection in a static that survives across tests in the
    // same process, so reset it to model a fresh request to the landing page.
    SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;
    SupportAutoInjectedAssets::$forceAssetInjection = false;

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('alpinejs')
        ->not->toContain('livewire.js')
        ->not->toContain('flux.js');
});
