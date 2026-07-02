<?php

declare(strict_types=1);

use App\Livewire\App\StripeOnboarding;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

afterEach(function () {
    if (class_exists(Mockery::class)) {
        Mockery::close();
    }
});

it('redirects org admins to stripe onboarding when accessing the app before connecting stripe', function () {
    $organization = Organization::factory()->withoutStripe()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($user)
        ->get(route('app'))
        ->assertRedirect(route('app.stripe-onboarding'));
});

it('redirects org admins to stripe onboarding when accessing app pages before connecting stripe', function () {
    $organization = Organization::factory()->withoutStripe()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($user)
        ->get(route('app.dashboard'))
        ->assertRedirect(route('app.stripe-onboarding'));
});

it('allows org admins to access the stripe onboarding page while not connected', function () {
    $organization = Organization::factory()->withoutStripe()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($user)
        ->get(route('app.stripe-onboarding'))
        ->assertOk()
        ->assertSee('Connect with Stripe Connect');
});

it('allows org admins with a connected account to access the app normally', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($user)
        ->get(route('app'))
        ->assertRedirect(route('app.dashboard'));

    actingAs($user)
        ->get(route('app.dashboard'))
        ->assertOk();
});

it('redirects to Stripe Connect OAuth when connect is clicked', function () {
    $organization = Organization::factory()->withoutStripe()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::test(StripeOnboarding::class)
        ->call('connect')
        ->assertRedirect(route('stripe.connect.redirect'));
});

it('redirects un-onboarded admins to Stripe OAuth from the connect redirect route', function () {
    $organization = Organization::factory()->withoutStripe()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $response = actingAs($user)
        ->get(route('stripe.connect.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://connect.stripe.com/oauth/authorize');
});

it('shows a success modal when returning from Stripe after onboarding', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::withQueryParams(['onboarding' => 'success'])
        ->test(StripeOnboarding::class)
        ->assertSet('showSuccessModal', true)
        ->assertSee('Stripe Onboarding Successful')
        ->assertSee('Settings > Payment', false);
});

it('redirects to campaigns when the success modal close button is clicked', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::withQueryParams(['onboarding' => 'success'])
        ->test(StripeOnboarding::class)
        ->call('closeSuccessModal')
        ->assertRedirect(route('app.campaigns.index'));
});

it('shows an already connected info modal when visiting onboarding while connected', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::test(StripeOnboarding::class)
        ->assertSet('alreadyConnected', true)
        ->assertSee('Stripe Connect Connected')
        ->assertSee('Settings > Payment', false);
});

it('redirects to stripe payment settings from the already connected modal', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::test(StripeOnboarding::class)
        ->call('goToStripeSettings')
        ->assertRedirect(route('app.settings.payment'));
});

it('redirects to dashboard when the already connected modal is closed', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    Livewire::test(StripeOnboarding::class)
        ->call('closeAlreadyConnectedModal')
        ->assertRedirect(route('app.dashboard'));
});
