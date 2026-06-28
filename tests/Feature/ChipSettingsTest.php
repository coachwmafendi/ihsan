<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]);
});

it('renders chip settings fields on the organization edit page', function () {
    $this->actingAs($this->user);

    Livewire::test(EditOrganization::class, ['record' => $this->organization->getKey()])
        ->assertFormFieldExists('chip_brand_id')
        ->assertFormFieldExists('chip_api_key')
        ->assertFormFieldExists('settings.chip_payment_methods');
});

it('saves chip brand id and api key', function () {
    $this->actingAs($this->user);

    Livewire::test(EditOrganization::class, ['record' => $this->organization->getKey()])
        ->fillForm([
            'chip_brand_id' => 'BRAND123',
            'chip_api_key' => 'secret-api-key',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->organization->refresh();

    expect($this->organization->chip_brand_id)->toBe('BRAND123')
        ->and($this->organization->chip_api_key)->toBe('secret-api-key')
        ->and($this->organization->chip_onboarded)->toBeTrue();
});

it('defaults chip payment methods to card', function () {
    $this->actingAs($this->user);

    Livewire::test(EditOrganization::class, ['record' => $this->organization->getKey()])
        ->fillForm([
            'chip_brand_id' => 'BRAND123',
            'chip_api_key' => 'secret-api-key',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->organization->refresh();

    expect($this->organization->settings['chip_payment_methods'] ?? [])->toBe(['card']);
});

it('saves chip payment method whitelist', function () {
    $this->actingAs($this->user);

    Livewire::test(EditOrganization::class, ['record' => $this->organization->getKey()])
        ->fillForm([
            'chip_brand_id' => 'BRAND123',
            'chip_api_key' => 'secret-api-key',
            'settings.chip_payment_methods' => ['card', 'fpx'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->organization->refresh();

    expect($this->organization->settings['chip_payment_methods'])->toBe(['card', 'fpx']);
});

it('reports chip onboarding status based on credentials presence', function () {
    $notOnboarded = Organization::factory()->create([
        'chip_brand_id' => null,
        'chip_api_key' => null,
    ]);

    $onboarded = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);

    expect($notOnboarded->chip_onboarded)->toBeFalse()
        ->and($onboarded->chip_onboarded)->toBeTrue();
});
