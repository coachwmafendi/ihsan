<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Settings\Tracking;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
});

it('toggles advanced and attribution sections on and off', function () {
    $this->actingAs($this->user);

    Livewire::test(Tracking::class)
        ->assertSet('showAdvanced', false)
        ->assertSet('showAttribution', false)
        ->call('toggleShowAdvanced')
        ->assertSet('showAdvanced', true)
        ->call('toggleShowAttribution')
        ->assertSet('showAttribution', true)
        ->call('toggleShowAdvanced')
        ->assertSet('showAdvanced', false)
        ->call('toggleShowAttribution')
        ->assertSet('showAttribution', false);
});
