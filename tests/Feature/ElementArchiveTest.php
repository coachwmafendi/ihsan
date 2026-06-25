<?php

use App\Livewire\App\Elements\ElementIndex;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('archives an element by setting archived_at', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $element = Element::factory()->for($org)->for($campaign)->create(['is_active' => true]);

    expect($element->archived_at)->toBeNull();
    expect($element->isArchived())->toBeFalse();

    $element->archive();

    expect($element->fresh()->archived_at)->not->toBeNull();
    expect($element->fresh()->isArchived())->toBeTrue();
    expect($element->fresh()->is_active)->toBeFalse();
});

it('excludes archived elements from the index listing', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($org)->create();

    $active = Element::factory()->for($org)->for($campaign)->create(['is_active' => true]);
    $archived = Element::factory()->for($org)->for($campaign)->create(['archived_at' => now(), 'is_active' => false]);

    Livewire::actingAs($user)
        ->test(ElementIndex::class)
        ->assertSee($active->name)
        ->assertDontSee($archived->name);
});

it('returns 404 for archived elements from public API', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $element = Element::factory()->for($org)->for($campaign)->create([
        'is_active' => false,
        'archived_at' => now(),
    ]);

    $this->get(route('api.public.elements.show', $element->token))
        ->assertStatus(404);
});

it('can unarchive an element', function () {
    $org = Organization::factory()->create();
    $element = Element::factory()->for($org)->create([
        'archived_at' => now(),
    ]);

    expect($element->isArchived())->toBeTrue();

    $element->unarchive();

    expect($element->fresh()->isArchived())->toBeFalse();
    expect($element->fresh()->archived_at)->toBeNull();
});

it('can restore an archived element from the index', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($org)->create();
    $element = Element::factory()->for($org)->for($campaign)->create([
        'archived_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(ElementIndex::class)
        ->call('unarchive', $element->id)
        ->assertDispatched('notify');

    expect($element->fresh()->archived_at)->toBeNull();
});

it('cannot restore an element belonging to another org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($otherOrg)->create();
    $element = Element::factory()->for($otherOrg)->for($campaign)->create([
        'archived_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(ElementIndex::class)
        ->call('unarchive', $element->id)
        ->assertForbidden();
});

it('toggleArchived switches between active and archived views', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($org)->create();

    $active = Element::factory()->for($org)->for($campaign)->create(['archived_at' => null]);
    $archived = Element::factory()->for($org)->for($campaign)->create(['archived_at' => now()]);

    $this->actingAs($user);

    $component = Livewire::test(ElementIndex::class);

    // Default: active view — active element visible, archived not
    $component->assertSee($active->name)->assertDontSee($archived->name);

    // After toggle: archived view — archived visible, active not
    $component->call('toggleArchived')
        ->assertSee($archived->name)
        ->assertDontSee($active->name);
});

it('can archive an element from the index', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($org)->create();
    $element = Element::factory()->for($org)->for($campaign)->create(['is_active' => true]);

    $this->actingAs($user);

    Livewire::test(ElementIndex::class)
        ->call('archive', $element->id)
        ->assertDispatched('notify');

    expect($element->fresh()->isArchived())->toBeTrue();
    expect($element->fresh()->is_active)->toBeFalse();
});

it('cannot archive an element belonging to another org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($otherOrg)->create();
    $element = Element::factory()->for($otherOrg)->for($campaign)->create(['is_active' => true]);

    $this->actingAs($user);

    Livewire::test(ElementIndex::class)
        ->call('archive', $element->id)
        ->assertForbidden();

    expect($element->fresh()->isArchived())->toBeFalse();
});
