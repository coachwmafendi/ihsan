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
        ->assertMountedActionModalSee('Edit Organization Admin')
        ->assertMountedActionModalSee('Full name')
        ->assertMountedActionModalSee('Email')
        ->assertMountedActionModalSeeHtml('ihsan-admin-editor-modal');
});

it('allows inviting the first admin when the organization has no admins', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]));

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->assertTableActionVisible('create');
});

it('hides the invite admin action when the organization already has an admin', function () {
    $organization = Organization::factory()->create();

    User::factory()->create([
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
        ->assertTableActionHidden('create');
});

it('renders static onboarding date and fee collection method on edit page', function () {
    $organization = Organization::factory()->create([
        'stripe_onboarded_at' => '2026-06-18 11:23:00',
        'fee_collection_method' => 'upfront',
    ]);

    $this->actingAs(User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]));

    Livewire::test(EditOrganization::class, ['record' => $organization->getKey()])
        ->assertFormFieldExists('fee_collection_method')
        ->assertSee('Upfront Payment')
        ->assertSee('Onboarded')
        ->assertSee('18/06/2026 19:23');
});
