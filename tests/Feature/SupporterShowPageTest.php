<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Supporters\SupporterShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('renders the supporter detail page with sections and menus', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->get('/app/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee($donor->name)
        ->assertSee('Information')
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Language')
        ->assertSee('Mailing address')
        ->assertSee('Donations')
        ->assertSee('Recurring plans')
        ->assertSee('Receipts')
        ->assertSee('Make donation')
        ->assertSee('Open Donor Portal')
        ->assertSee('Information');
});

it('marks the active section menu with intersection observer data', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $component = Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor]);

    $component->assertSeeHtml('id="information"')
        ->assertSeeHtml('id="donations"')
        ->assertSeeHtml('id="recurring-plans"')
        ->assertSeeHtml('id="receipts"')
        ->assertSeeHtml('href="#information"')
        ->assertSeeHtml('href="#donations"')
        ->assertSeeHtml('href="#recurring-plans"')
        ->assertSeeHtml('href="#receipts"')
        ->assertSeeHtml('sticky top-24');
});

it('opens the edit modal and saves the supporter details', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['name' => 'Ali Abu', 'email' => 'ali@example.com']);
    Donation::factory()->for($donor)->for($campaign)->create();

    $component = Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor]);

    $component->assertSeeHtml('wire:click="openEditModal"')
        ->call('openEditModal')
        ->assertSet('editing', true)
        ->assertSet('firstName', 'Ali')
        ->assertSet('lastName', 'Abu')
        ->assertSet('email', 'ali@example.com');

    $component->set('firstName', 'Siti')
        ->set('lastName', 'Aminah')
        ->set('email', 'siti@example.com')
        ->set('updateRecurringPlans', false)
        ->call('save');

    expect($donor->fresh())
        ->name->toBe('Siti Aminah')
        ->email->toBe('siti@example.com');

    $component->assertSet('editing', false);
});
