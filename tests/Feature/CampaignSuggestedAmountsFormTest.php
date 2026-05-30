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
        ->assertSee('Overview')
        ->assertSee('Checkout Modal Settings')
        ->assertSee('Umum')
        ->assertSee('Jumlah disarankan')
        ->assertSee('Pra-set kekerapan')
        ->assertSee('RM (MYR)')
        ->assertSee('$ (USD)')
        ->assertSee('S$ (SGD)')
        ->assertSee('Tetapkan butang derma untuk setiap mata wang yang diterima.')
        ->assertSee('Sekali sahaja')
        ->assertSee('Bulanan')
        ->assertSee('Jumlah pra-set')
        ->assertSee(':key="`${activeCurrency}-${activeTab}-${index}`"', false)
        ->assertSee('symbolFor(activeCurrency)', false)
        ->assertSee('Tambah jumlah')
        ->assertSee('Bulanan lalai')
        ->assertSee('Penderma lihat jumlah ini sebagai pra-pilih untuk derma bulanan.');
});

it('saves checkout modal settings on the campaign form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateCampaign::class)
        ->fillForm([
            'title' => 'Ramadan Relief Fund',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $campaign = $organization->campaigns()->latest()->firstOrFail();

    expect($campaign->organization_id)->toBe($organization->getKey())
        ->and($campaign->form_parameter)->toMatch('/^[A-Z0-9]{5}$/');
});
