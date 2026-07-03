<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Elements\ElementIndex;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects to the element edit page via navigate when a row is clicked', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $org->id,
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->create(['organization_id' => $org->id]);
    $element = Element::factory()->create([
        'organization_id' => $org->id,
        'campaign_id' => $campaign->id,
    ]);

    Livewire::actingAs($user)
        ->test(ElementIndex::class)
        ->call('edit', $element->id)
        ->assertRedirect(route('app.elements.edit', $element));
});
