<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers fortify login on the app panel domain', function () {
    $route = app('router')->getRoutes()->getByName('login');

    expect($route->getDomain())->toBe('app.example.test');
    expect(app('router')->getRoutes()->getByName('logout')->getDomain())->toBe('app.example.test');
});

it('redirects to the dashboard after login', function () {
    expect(config('fortify.home'))->toBe('/dashboard');
});

it('serves the dashboard on the app panel domain', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/dashboard')
        ->assertOk();
});

it('redirects the app panel root to the dashboard', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/')
        ->assertRedirect(route('app.dashboard'));
});
