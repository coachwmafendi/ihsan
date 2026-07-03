<?php

declare(strict_types=1);

use App\Livewire\App\CommandPalette;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
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

it('closes via closePalette and resets the query', function () {
    Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->set('open', true)
        ->set('query', 'test')
        ->call('closePalette')
        ->assertSet('open', false)
        ->assertSet('query', '');
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

it('filters pages and actions by query', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->set('query', 'camp');

    $items = $component->instance()->items();

    expect($items['pages'])->toHaveCount(1);
    expect($items['pages'][0]['label'])->toBe('Campaigns');

    expect($items['actions'])->toHaveCount(1);
    expect($items['actions'][0]['label'])->toBe('Create campaign');
});

it('searches supporters by email', function () {
    $donor = Donor::factory()->create([
        'name' => 'Wan Afendi',
        'email' => 'wmafendi@gmail.com',
    ]);
    $campaign = Campaign::factory()->for($this->organization)->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    Livewire::actingAs($this->user);

    $component = Livewire::test(CommandPalette::class)
        ->set('query', 'wmafendi');

    $supporters = $component->instance()->supporters();

    expect($supporters)->toHaveCount(1);
    expect($supporters[0]['label'])->toBe('Wan Afendi');
    expect($supporters[0]['sublabel'])->toBe('wmafendi@gmail.com');
});

it('only returns supporters with donations to the users organization', function () {
    $otherOrg = Organization::factory()->create();
    $donor = Donor::factory()->create([
        'name' => 'External Donor',
        'email' => 'external@example.com',
    ]);
    $campaign = Campaign::factory()->for($otherOrg)->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $component = Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->set('query', 'external');

    expect($component->instance()->supporters())->toBeEmpty();
});

it('requires at least two characters to search supporters', function () {
    Donor::factory()->create(['name' => 'Test Donor', 'email' => 'test@example.com']);

    $component = Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->set('query', 't');

    expect($component->instance()->supporters())->toBeEmpty();
});

it('limits supporter results to ten', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();

    Donor::factory()->count(15)->sequence(
        fn ($sequence) => ['email' => 'donor-'.$sequence->index.'@gmail.com']
    )->create()->each(fn (Donor $donor) => Donation::factory()->for($donor)->for($campaign)->create());

    $component = Livewire::actingAs($this->user)
        ->test(CommandPalette::class)
        ->set('query', 'gmail');

    expect($component->instance()->supporters())->toHaveCount(10);
});
