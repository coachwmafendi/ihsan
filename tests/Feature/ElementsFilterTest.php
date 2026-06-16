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

it('filters elements by inactive status via url', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $org->id,
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->create(['organization_id' => $org->id]);

    $active = Element::factory()->create([
        'organization_id' => $org->id,
        'campaign_id' => $campaign->id,
        'is_active' => true,
        'archived_at' => null,
    ]);

    $inactive = Element::factory()->create([
        'organization_id' => $org->id,
        'campaign_id' => $campaign->id,
        'is_active' => false,
        'archived_at' => null,
    ]);

    Livewire::actingAs($user)
        ->withQueryParams(['statusFilter' => 'inactive'])
        ->test(ElementIndex::class)
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});

it('shows archived elements with restore action when toggled', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $org->id,
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->create(['organization_id' => $org->id]);

    $archived = Element::factory()->create([
        'organization_id' => $org->id,
        'campaign_id' => $campaign->id,
        'is_active' => true,
        'archived_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ElementIndex::class)
        ->assertDontSee($archived->name)
        ->call('toggleArchived')
        ->assertSee($archived->name)
        ->assertSee('Restore');
});
