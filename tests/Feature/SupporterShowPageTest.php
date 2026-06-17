<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Supporters\SupporterShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
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
        ->assertSee('Receipts')
        ->assertSee('Make donation')
        ->assertSee('Open Donor Portal')
        ->assertSee('Information');
});

it('shows approximate myr lifetime total when foreign donations lack base amount', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($donor)->for($campaign)->create([
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => null,
    ]);
    Donation::factory()->for($donor)->for($campaign)->create([
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => null,
    ]);

    $this->actingAs($user)
        ->get('/app/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Lifetime donated ≈ MYR 200.00');
});

it('hides recurring plans section and menu when supporter has no subscriptions', function () {
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
        ->assertDontSee('Recurring plans')
        ->assertDontSeeHtml('id="recurring-plans"')
        ->assertDontSeeHtml('href="#recurring-plans"');
});

it('shows recurring plans section and menu when supporter has subscriptions', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();
    Subscription::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->get('/app/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Recurring plans')
        ->assertSeeHtml('id="recurring-plans"')
        ->assertSeeHtml('href="#recurring-plans"');
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
        ->assertSeeHtml('id="receipts"')
        ->assertSeeHtml('href="#information"')
        ->assertSeeHtml('href="#donations"')
        ->assertSeeHtml('href="#receipts"')
        ->assertSeeHtml('sticky top-24');
});

it('marks the active section menu including recurring plans when subscriptions exist', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();
    Subscription::factory()->for($donor)->for($campaign)->create();

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

it('renders an impersonation form on the open donor portal action', function () {
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
        ->assertSee('Open Donor Portal')
        ->assertSeeHtml('action="'.e(route('admin.donor-portal.impersonate', $donor)).'"')
        ->assertSeeHtml('target="_blank"');
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
