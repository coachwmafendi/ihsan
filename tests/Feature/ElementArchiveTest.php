<?php

use App\Models\Element;
use App\Models\Organization;
use App\Models\Campaign;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
    $org = \App\Models\Organization::factory()->create();
    $user = \App\Models\User::factory()->for($org)->create();
    $campaign = \App\Models\Campaign::factory()->for($org)->create();

    $active = \App\Models\Element::factory()->for($org)->for($campaign)->create(['is_active' => true]);
    $archived = \App\Models\Element::factory()->for($org)->for($campaign)->create(['archived_at' => now(), 'is_active' => false]);

    \Livewire\Livewire::actingAs($user)
        ->test(\App\Livewire\App\Elements\ElementIndex::class)
        ->assertSee($active->name)
        ->assertDontSee($archived->name);
});

it('returns 404 for archived elements from public API', function () {
    $org = \App\Models\Organization::factory()->create();
    $campaign = \App\Models\Campaign::factory()->for($org)->create();
    $element = \App\Models\Element::factory()->for($org)->for($campaign)->create([
        'is_active' => false,
        'archived_at' => now(),
    ]);

    $this->get(route('api.public.elements.show', $element->token))
        ->assertStatus(404);
});
