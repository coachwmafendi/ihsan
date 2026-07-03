<?php

declare(strict_types=1);

use App\Livewire\App\CommandPalette;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create();
});

it('starts closed', function () {
    Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->assertSet('open', false);
});

it('opens when the open-command-palette event is dispatched', function () {
    Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->assertSet('open', true);
});

it('closes via closePalette', function () {
    Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->set('open', true)
        ->call('closePalette')
        ->assertSet('open', false);
});

it('exposes the expected pages and actions', function () {
    $component = Livewire::actingAs($this->user)->test(CommandPalette::class);

    $items = $component->instance()->items();

    expect($items['pages'])->toHaveCount(4);
    expect(collect($items['pages'])->pluck('label')->all())
        ->toBe(['Dashboard', 'Supporters', 'Donations', 'Campaigns']);

    expect(collect($items['actions'])->pluck('label')->all())
        ->toBe(['New element', 'Create campaign']);

    expect(collect($items['actions'])->pluck('hotkey')->all())
        ->toBe(['E', 'K']);
});
