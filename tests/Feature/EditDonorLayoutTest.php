<?php

use App\Enums\UserRole;
use App\Livewire\App\Donors\DonorShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('renders the donor show page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'name' => 'Aminah Hassan',
        'email' => 'aminah@example.test',
    ]);

    Donation::factory()->for($campaign)->for($donor)->create();

    $this->actingAs($user);

    Livewire::test(DonorShow::class, [
        'donor' => $donor,
    ])
        ->assertOk()
        ->assertSee('Aminah Hassan')
        ->assertSee('aminah@example.test');
});

it('renders a donations section for succeeded donations', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 101.69,
        'base_amount' => 101.69,
        'invoice_number' => 'DZSZESVS',
        'created_at' => now()->subMonth(),
    ]);

    $this->actingAs($user);

    Livewire::test(DonorShow::class, [
        'donor' => $donor,
    ])
        ->assertOk()
        ->assertSee('Recent Donations')
        ->assertSee('RM 101.69');
});
