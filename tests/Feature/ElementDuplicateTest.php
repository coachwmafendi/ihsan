<?php

use App\Livewire\App\Elements\ElementIndex;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('duplicates an element from the index', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($org)->create();

    $element = Element::factory()->for($org)->for($campaign)->create([
        'name' => 'Hero Button',
        'type' => 'button',
        'config' => ['button_text' => 'Donate'],
        'is_active' => true,
        'form_slug' => 'hero-button',
    ]);

    $this->actingAs($user);

    Livewire::test(ElementIndex::class)
        ->call('duplicate', $element->id)
        ->assertDispatched('notify');

    $copy = Element::where('name', 'Hero Button (Copy)')->first();

    expect($copy)->not->toBeNull();
    expect($copy->organization_id)->toBe($element->organization_id);
    expect($copy->campaign_id)->toBe($element->campaign_id);
    expect($copy->type->value)->toBe($element->type->value);
    expect($copy->config)->toBe($element->config);
    expect($copy->token)->not->toBe($element->token);
    expect($copy->public_id)->not->toBe($element->public_id);
    expect($copy->form_slug)->toBeNull();
    expect($copy->is_active)->toBeTrue();
    expect($copy->isArchived())->toBeFalse();
});

it('cannot duplicate an element belonging to another org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($otherOrg)->create();
    $element = Element::factory()->for($otherOrg)->for($campaign)->create();

    $this->actingAs($user);

    Livewire::test(ElementIndex::class)
        ->call('duplicate', $element->id)
        ->assertForbidden();

    expect(Element::count())->toBe(1);
});
