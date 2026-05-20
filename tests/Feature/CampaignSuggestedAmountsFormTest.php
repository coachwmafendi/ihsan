<?php

use App\Enums\UserRole;
use App\Filament\App\Resources\Campaigns\Pages\CreateCampaign;
use App\Models\Campaign;
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
        ->assertSee('Overview')
        ->assertSee('Settings')
        ->assertSee('Checkout Modal')
        ->assertSee('Campaign Page')
        ->assertSee('General')
        ->assertSee('Frequencies')
        ->assertSee('Payment')
        ->assertSee('Supporter experience')
        ->assertSee('Enable modal checkout')
        ->assertSee('Allowed domains')
        ->assertSee('Install script')
        ->assertSee('Script tag')
        ->assertSee('Button trigger')
        ->assertSee('Link trigger')
        ->assertSee('<script src="https://ihsan.test/embed.js" async></script>')
        ->assertSee('data-ihsan-form="FORM_PARAMETER"')
        ->assertSee('Frequency presets')
        ->assertSee('One-time')
        ->assertSee('Monthly')
        ->assertSee('Preset amounts')
        ->assertSee('get sortedAmounts()', false)
        ->assertSee(':key="`${activeTab}-${index}`"', false)
        ->assertDontSee(':key="`${activeTab}-${index}-${amount.amount}`"', false)
        ->assertSee('Add amount')
        ->assertSee('Monthly default')
        ->assertSee('Donors see this amount preselected when monthly giving is active.');
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
            'slug' => 'ramadan-relief-fund',
            'status' => 'active',
            'checkout_modal_enabled' => true,
            'form_parameter' => 'RAMADAN2026',
            'checkout_allowed_domains' => [
                'mumzatuttaqwa.com',
                'www.mumzatuttaqwa.com',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $campaign = Campaign::query()
        ->where('slug', 'ramadan-relief-fund')
        ->firstOrFail();

    expect($campaign->organization_id)->toBe($organization->getKey())
        ->and($campaign->checkout_modal_enabled)->toBeTrue()
        ->and($campaign->form_parameter)->toBe('RAMADAN2026')
        ->and($campaign->checkout_allowed_domains)->toBe([
            'mumzatuttaqwa.com',
            'www.mumzatuttaqwa.com',
        ]);
});
