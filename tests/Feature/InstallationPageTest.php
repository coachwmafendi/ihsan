<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;

it('shows both embed options and the loader snippet', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user)
        ->get(route('app.settings.installation'))
        ->assertOk()
        ->assertSee('Copy-paste ready code', false)
        ->assertSee('One script for custom buttons', false)
        ->assertSee('No extra script needed', false)
        ->assertSee('Easiest option', false)
        ->assertSee('Developer / advanced option', false)
        ->assertSee(url('/e/loader.js'), false)
        ->assertSee('data-ihsan-loader', false);
});
