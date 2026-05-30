<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;

it('redirects app panel dashboard to insights', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect(route('filament.app.pages.insights'));
});

it('allows super admins into the admin panel', function () {
    $user = User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(route('filament.admin.pages.platform-overview'));
});

it('keeps panel roles separated', function () {
    $organization = Organization::factory()->create();
    $ngoAdmin = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]);

    $this->actingAs($ngoAdmin)
        ->get('/admin')
        ->assertRedirect(route('filament.admin.pages.platform-overview'));

    $this->actingAs($ngoAdmin)
        ->get('/admin/platform-overview')
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->get('/app')
        ->assertForbidden();
});

it('shows app panel resource pages to ngo admins', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);

    foreach ([
        '/app/insights',
        '/app/campaigns',
        '/app/elements',
        '/app/donations',
        '/app/subscriptions',
        '/app/supporters',
    ] as $path) {
        $this->get($path)->assertSuccessful();
    }
});
