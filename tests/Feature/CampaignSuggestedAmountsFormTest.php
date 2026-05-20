<?php

use App\Enums\UserRole;
use App\Filament\App\Resources\Campaigns\Pages\CreateCampaign;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('renders a polished suggested amounts editor on the campaign form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateCampaign::class)
        ->assertOk()
        ->assertSee('suggested-amounts-editor', false)
        ->assertSee('Frequency presets')
        ->assertSee('One-time')
        ->assertSee('Monthly')
        ->assertSee('Preset amounts')
        ->assertSee('Monthly default')
        ->assertSee('Donors see this amount preselected when monthly giving is active.');
});
