<?php

declare(strict_types=1);

use App\Actions\Stripe\CreateConnectAccount;
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
        ->get(route('app.insights'))
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
        ->assertRedirect(route('app.insights'));

    actingAs($user)
        ->get(route('app.insights'))
        ->assertOk();
});

it('creates a stripe connect account and redirects to stripe when connect is clicked', function () {
    $organization = Organization::factory()->withoutStripe()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $service = $this->mock(CreateConnectAccount::class);
    $service->shouldReceive('create')->once()->withAnyArgs();
    $service->shouldReceive('generateOnboardingLink')->once()->withAnyArgs()->andReturn('https://connect.stripe.com/setup/s/onboarding');

    $this->actingAs($user);

    Livewire::test(StripeOnboarding::class)
        ->call('connect')
        ->assertRedirect('https://connect.stripe.com/setup/s/onboarding');
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
        ->assertSee('Stripe Onboarding Successful');
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
