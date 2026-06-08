<?php

use App\Enums\UserRole;
use App\Filament\App\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\App\Resources\Donations\Pages\ListDonations;
use App\Filament\App\Resources\Donors\DonorResource;
use App\Filament\App\Resources\Donors\Pages\ListDonors;
use App\Filament\App\Resources\Elements\Pages\ListElements;
use App\Filament\App\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('shows useful dummy data across ngo app resource tables', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Dana Makanan Pelajar',
        'status' => 'active',
        'collected_amount' => 1250.00,
    ]);

    $donor = Donor::factory()->create([
        'name' => 'Hafizah Ahmad',
        'email' => 'hafizah@example.test',
        'public_id' => 'DRGD3JY8',
    ]);

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 120.00,
        'status' => 'succeeded',
        'type' => 'one_time',
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 50.00,
        'status' => 'active',
        'interval' => 'monthly',
    ]);

    Element::factory()->for($organization)->for($campaign)->create([
        'name' => 'Homepage Donation Form',
        'type' => 'form',
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(ListCampaigns::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$campaign])
        ->assertSee('Dana Makanan Pelajar')
        ->assertSee('active');

    Livewire::test(ListDonations::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$campaign->donations()->first()])
        ->assertSee('Hafizah Ahmad')
        ->assertSee('Dana Makanan Pelajar')
        ->assertSee('120.00');

    $listDonors = Livewire::test(ListDonors::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$donor])
        ->assertActionHasUrl(
            TestAction::make('view')->table($donor),
            DonorResource::getUrl('view', ['record' => $donor->public_id]),
        )
        ->assertSee('Hafizah Ahmad')
        ->assertSee('hafizah@example.test');

    expect($listDonors->instance()->getTable()->getRecordUrl($donor))
        ->toBe(DonorResource::getUrl('view', ['record' => $donor->public_id]));

    Livewire::test(ListSubscriptions::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$campaign->subscriptions()->first()])
        ->assertSee('Hafizah Ahmad')
        ->assertSee('monthly')
        ->assertSee('50.00');

    Livewire::test(ListElements::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$campaign->elements()->first()])
        ->assertSee('Homepage Donation Form')
        ->assertSee('form');
});
