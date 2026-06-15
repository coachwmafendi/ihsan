<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('archives element and redirects to index', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\App\Elements\ElementEdit::class, ['element' => $element])
        ->call('archive')
        ->assertRedirect(route('app.elements.index'));

    expect($element->fresh()->archived_at)->not->toBeNull();
    expect($element->fresh()->is_active)->toBeFalse();
});

it('does not hard delete element on archive', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\App\Elements\ElementEdit::class, ['element' => $element])
        ->call('archive');

    expect(Element::find($element->id))->not->toBeNull();
});
