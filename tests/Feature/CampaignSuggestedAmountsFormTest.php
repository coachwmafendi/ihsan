<?php

use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('renders a polished suggested amounts editor on the campaign form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignCreate::class)
        ->assertOk()
        ->assertSee('Create Campaign')
        ->assertSee('Suggested Amounts');
});

it('saves checkout modal settings on the campaign form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Ramadan Relief Fund')
        ->set('status', 'active')
        ->call('save');

    $campaign = $organization->campaigns()->latest()->firstOrFail();

    expect($campaign->organization_id)->toBe($organization->getKey())
        ->and($campaign->form_parameter)->toMatch('/^[A-Z0-9]{5}$/');
});
