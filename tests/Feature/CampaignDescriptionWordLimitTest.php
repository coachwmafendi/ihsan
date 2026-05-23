<?php

use App\Enums\UserRole;
use App\Filament\App\Resources\Campaigns\Pages\CreateCampaign;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('validates that the description does not exceed 100 words', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $longDescription = implode(' ', array_fill(0, 101, 'perkataan'));

    Livewire::test(CreateCampaign::class)
        ->fillForm([
            'title' => 'Test Campaign',
            'slug' => 'test-campaign',
            'status' => 'active',
            'description' => $longDescription,
        ])
        ->call('create')
        ->assertHasFormErrors(['description']);
});

it('allows description with exactly 100 words', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $validDescription = implode(' ', array_fill(0, 100, 'perkataan'));

    Livewire::test(CreateCampaign::class)
        ->fillForm([
            'title' => 'Test Campaign',
            'slug' => 'test-campaign',
            'status' => 'active',
            'description' => $validDescription,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();
});
