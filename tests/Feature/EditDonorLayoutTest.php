<?php

use App\Enums\UserRole;
use App\Filament\App\Resources\Donors\Pages\EditDonor;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Livewire;

it('uses a view record page for the supporter edit URL', function () {
    expect(is_subclass_of(EditDonor::class, ViewRecord::class))->toBeTrue();
});

it('renders the supporter information section at full content width', function () {
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
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(EditDonor::class, [
        'record' => $donor->getRouteKey(),
    ])
        ->assertOk()
        ->assertDontSee('Delete')
        ->assertSee('Information')
        ->assertSeeHtml('--col-span-default: 1 / -1');
});

it('renders a dedicated receipts section for succeeded donations', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 101.69,
        'base_amount' => 101.69,
        'invoice_number' => 'DZSZESVS',
        'created_at' => now()->subMonth(),
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(EditDonor::class, [
        'record' => $donor->getRouteKey(),
    ])
        ->assertOk()
        ->assertSee('Receipts')
        ->assertSee('DZSZESVS')
        ->assertSee('MYR 101.69')
        ->assertSeeHtml('id="receipts-section"')
        ->assertSeeHtml("scrollTo('receipts-section')")
        ->assertSee(route('donations.receipt.download', $donation), false);
});
