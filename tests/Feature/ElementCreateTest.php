<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Elements\ElementCreate;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
});

it('toggles the active setting on and off', function () {
    $this->actingAs($this->user);

    Livewire::test(ElementCreate::class)
        ->assertSet('is_active', true)
        ->call('toggleIsActive')
        ->assertSet('is_active', false)
        ->call('toggleIsActive')
        ->assertSet('is_active', true);
});
