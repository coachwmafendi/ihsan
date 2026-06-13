<?php

use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('validates that the description does not exceed 100 words', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);

    $longDescription = implode(' ', array_fill(0, 101, 'perkataan'));

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Test Campaign')
        ->set('status', 'active')
        ->set('description', $longDescription)
        ->call('save')
        ->assertHasErrors(['description']);
});

it('allows description with exactly 100 words', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);

    $validDescription = implode(' ', array_fill(0, 100, 'perkataan'));

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Test Campaign')
        ->set('status', 'active')
        ->set('description', $validDescription)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('campaigns', [
        'title' => 'Test Campaign',
        'organization_id' => $organization->id,
    ]);
});
