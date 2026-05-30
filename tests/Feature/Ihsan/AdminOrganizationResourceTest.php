<?php

use App\Enums\UserRole;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('shows pending organizations by default in the super admin table', function () {
    $organization = Organization::factory()->create([
        'name' => 'Maahad Tahfiz Mumtazatut Taqwa',
        'status' => 'pending',
    ]);

    $this->actingAs(User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]));

    Livewire::test(ListOrganizations::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$organization])
        ->assertSee('Maahad Tahfiz Mumtazatut Taqwa')
        ->assertSee('Pending');
});

it('renders the polished edit admin modal', function () {
    $organization = Organization::factory()->create();

    $organizationAdmin = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::NgoAdmin,
        'name' => 'Ahmad bin Ali',
        'email' => 'ahmad@example.com',
    ]);

    $this->actingAs(User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]));

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->mountAction(TestAction::make('edit')->table($organizationAdmin))
        ->assertMountedActionModalSee('Edit Organisation Admin')
        ->assertMountedActionModalSee('Full name')
        ->assertMountedActionModalSee('Email')
        ->assertMountedActionModalSeeHtml('ihsan-admin-editor-modal');
});
