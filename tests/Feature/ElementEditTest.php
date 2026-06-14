<?php

declare(strict_types=1);

use App\Enums\ElementType;
use App\Enums\UserRole;
use App\Http\Controllers\PublicElementController;
use App\Livewire\App\Elements\ElementEdit;
use App\Livewire\App\Elements\ElementIndex;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
});

it('loads button config defaults when editing a button element', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->assertSet('config_button_color', 'bg-blue-600 hover:bg-blue-700')
        ->assertSet('config_button_size', 'text-base px-6 py-3')
        ->assertSet('config_corner_radius', 8)
        ->assertSet('config_button_icon', 'heart')
        ->assertSet('config_button_effect', 'none');
});

it('saves button colour, size, radius and icon config', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_text', 'Sumbangkan')
        ->set('config_button_color', 'bg-teal-600 hover:bg-teal-700')
        ->set('config_button_size', 'text-lg px-8 py-4')
        ->set('config_corner_radius', 12)
        ->set('config_button_icon', 'star')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element->refresh();

    expect($element->config)->toMatchArray([
        'button_text' => 'Sumbangkan',
        'button_color' => 'bg-teal-600 hover:bg-teal-700',
        'button_size' => 'text-lg px-8 py-4',
        'corner_radius' => 12,
        'button_icon' => 'star',
    ])
        ->and($element->config)->not->toHaveKey('title')
        ->and($element->config)->not->toHaveKey('message');
});

it('supports no icon option for button elements', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_icon', 'none')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['button_icon'])->toBe('none');
});

it('exposes button icon in the public element api', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
        'config' => [
            'button_color' => 'bg-orange-600 hover:bg-orange-700',
            'button_size' => 'text-sm px-4 py-2',
            'corner_radius' => 4,
            'button_icon' => 'gift',
        ],
    ]);

    $response = app(PublicElementController::class)->show($element->token);

    expect($response->getData(true)['settings'])
        ->toMatchArray([
            'button_color' => 'bg-orange-600 hover:bg-orange-700',
            'button_size' => 'text-sm px-4 py-2',
            'corner_radius' => 4,
            'button_icon' => 'gift',
        ]);
});

it('preserves existing button effect config on save', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
        'config' => [
            'button_effect' => 'gradient_teal_green',
            'button_color' => 'bg-blue-600 hover:bg-blue-700',
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_size', 'text-sm px-4 py-2')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['button_effect'])->toBe('gradient_teal_green');
});

it('saves a gradient button effect', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_text', 'Derma')
        ->set('config_button_effect', 'gradient_blue_purple')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['button_effect'])->toBe('gradient_blue_purple')
        ->and($element->config['button_text'])->toBe('Derma');
});

it('creates button elements with default Donate text and heart icon', function () {
    $this->actingAs($this->user);

    Livewire::test(ElementIndex::class)
        ->call('openCreateModal')
        ->set('newType', 'button')
        ->set('newName', 'Masjid Fasa 1 Button')
        ->set('newCampaignId', $this->campaign->id)
        ->call('createElement')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element = Element::query()
        ->where('name', 'Masjid Fasa 1 Button')
        ->firstOrFail();

    expect($element->config['button_text'])->toBe('Donate')
        ->and($element->config['icon'])->toBe('heart');
});

it('creates a link element with default Donate text', function () {
    $this->actingAs($this->user);

    Livewire::test(ElementIndex::class)
        ->call('openCreateModal')
        ->set('newType', 'link')
        ->set('newName', 'Donation Link')
        ->set('newCampaignId', $this->campaign->id)
        ->call('createElement')
        ->assertHasNoErrors();

    $element = Element::query()
        ->where('name', 'Donation Link')
        ->firstOrFail();

    expect($element->config['button_text'])->toBe('Donate')
        ->and($element->config['text'])->toBe('Donate');
});
