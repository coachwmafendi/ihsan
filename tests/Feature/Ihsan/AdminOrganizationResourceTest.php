<?php

use App\Enums\UserRole;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Models\Organization;
use App\Models\User;
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
