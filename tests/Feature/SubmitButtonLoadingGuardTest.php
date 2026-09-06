<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Livewire\App\Elements\ElementCreate;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($this->user);
});

it('disables the campaign create submit button while the form is saving', function () {
    Livewire::test(CampaignCreate::class)
        ->assertSee('wire:loading.attr="disabled"', escape: false);
});

it('disables the element create submit button while the form is saving', function () {
    Campaign::factory()->for($this->organization)->create();

    Livewire::test(ElementCreate::class)
        ->assertSee('wire:loading.attr="disabled"', escape: false);
});

it('leaves ordinary buttons clickable during a request', function () {
    $html = Blade::render('<x-ui.button wire-click="doThing">Do thing</x-ui.button>');

    expect($html)->not->toContain('wire:loading.attr');
});

it('marks any submit button as busy while its form is in flight', function () {
    $html = Blade::render('<x-ui.button type="submit">Save</x-ui.button>');

    expect($html)->toContain('wire:loading.attr="disabled"');
});
