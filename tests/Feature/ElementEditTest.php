<?php

declare(strict_types=1);

use App\Enums\ElementType;
use App\Enums\UserRole;
use App\Http\Controllers\PublicElementController;
use App\Livewire\App\Elements\ElementCreate;
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
        ->assertSet('config_button_size', 'medium')
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
        ->set('config_button_size', 'large')
        ->set('config_corner_radius', 12)
        ->set('config_button_icon', 'star')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element->refresh();

    expect($element->config)->toMatchArray([
        'button_text' => 'Sumbangkan',
        'button_color' => 'bg-teal-600 hover:bg-teal-700',
        'button_size' => 'large',
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
            'button_size' => 'small',
            'corner_radius' => 4,
            'button_icon' => 'gift',
        ],
    ]);

    $response = app(PublicElementController::class)->show($element->token);

    expect($response->getData(true)['settings'])
        ->toMatchArray([
            'button_color' => 'bg-orange-600 hover:bg-orange-700',
            'button_size' => 'small',
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
        ->set('config_button_size', 'small')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['button_effect'])->toBe('gradient_teal_green');
});

it('renders a gradient effect in the button preview', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
        'config' => ['button_effect' => 'gradient_emerald_teal'],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->assertSee('preview-effect-')
        ->assertSee('linear-gradient(120deg,#10b981,#0d9488,#34d399,#10b981)');
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
        ->and($element->config['button_icon'])->toBe('heart')
        ->and($element->config['button_color'])->toBe('bg-blue-600 hover:bg-blue-700');
});

it('loads button config defaults when editing a sticky button element', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::StickyButton,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->assertSet('config_button_color', 'bg-blue-600 hover:bg-blue-700')
        ->assertSet('config_button_size', 'medium')
        ->assertSet('config_corner_radius', 8)
        ->assertSet('config_button_icon', 'heart')
        ->assertSet('config_button_effect', 'none')
        ->assertSet('config_position', 'right-center');
});

it('saves button style config for sticky button elements', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::StickyButton,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_text', 'Sumbang')
        ->set('config_button_color', 'bg-teal-600 hover:bg-teal-700')
        ->set('config_button_size', 'large')
        ->set('config_corner_radius', 12)
        ->set('config_button_icon', 'star')
        ->set('config_button_effect', 'gradient_blue_purple')
        ->set('config_position', 'left-center')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element->refresh();

    expect($element->config)->toMatchArray([
        'button_text' => 'Sumbang',
        'button_color' => 'bg-teal-600 hover:bg-teal-700',
        'button_size' => 'large',
        'corner_radius' => 12,
        'button_icon' => 'star',
        'button_effect' => 'gradient_blue_purple',
        'position' => 'left-center',
    ])
        ->and($element->config)->not->toHaveKey('title')
        ->and($element->config)->not->toHaveKey('message');
});

it('creates sticky button elements with default button style config', function () {
    $this->actingAs($this->user);

    Livewire::test(ElementIndex::class)
        ->call('openCreateModal')
        ->set('newType', 'sticky_button')
        ->set('newName', 'Sticky Masjid Button')
        ->set('newCampaignId', $this->campaign->id)
        ->call('createElement')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element = Element::query()
        ->where('name', 'Sticky Masjid Button')
        ->firstOrFail();

    expect($element->config['button_text'])->toBe('Donate')
        ->and($element->config['button_icon'])->toBe('heart')
        ->and($element->config['button_color'])->toBe('bg-blue-600 hover:bg-blue-700')
        ->and($element->config['button_size'])->toBe('medium')
        ->and($element->config['corner_radius'])->toBe(8)
        ->and($element->config['button_effect'])->toBe('none')
        ->and($element->config['position'])->toBe('right-center');
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

it('maps form button text to submit_text config', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Form,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_title', 'Bantu pembinaan surau')
        ->set('config_message', 'Sumbangan anda penting untuk komuniti.')
        ->set('config_button_text', 'Sumbang Sekarang')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config)
        ->toMatchArray([
            'title' => 'Bantu pembinaan surau',
            'message' => 'Sumbangan anda penting untuk komuniti.',
            'button_text' => 'Sumbang Sekarang',
            'submit_text' => 'Sumbang Sekarang',
        ]);
});

it('loads existing submit_text into button text for form elements', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Form,
        'config' => [
            'submit_text' => 'Tabung Kecemasan',
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->assertSet('config_button_text', 'Tabung Kecemasan');
});

it('saves the icon config for a link element', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Link,
        'config' => [
            'button_text' => 'Donate',
            'text' => 'Donate',
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_icon', 'star')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['button_icon'])->toBe('star')
        ->and($element->config['action'])->toBe('checkout_modal');
});

it('enforces a 100 character limit on the message field when editing', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_message', str_repeat('a', 101))
        ->call('save')
        ->assertHasErrors(['config_message' => 'max']);

    $element->refresh();

    expect($element->config)->not->toHaveKey('message');
});

it('allows a message up to 100 characters when editing', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_message', str_repeat('a', 100))
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['message'])->toHaveLength(100);
});

it('enforces a 100 character limit on the message field when creating', function () {
    $this->actingAs($this->user);

    Livewire::test(ElementCreate::class)
        ->set('campaign_id', $this->campaign->id)
        ->set('type', 'popup')
        ->set('name', 'Popup CTA')
        ->set('config_message', str_repeat('a', 101))
        ->call('save')
        ->assertHasErrors(['config_message' => 'max']);
});

it('loads popup config defaults when editing a popup element', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->assertSet('config_action', 'checkout_modal')
        ->assertSet('config_trigger', 'after_delay')
        ->assertSet('config_delay', 8)
        ->assertSet('config_frequency', 'once_per_day')
        ->assertSet('config_visibility', 'desktop_mobile')
        ->assertSet('config_layout', 'simple')
        ->assertSet('config_image_url', 'https://images.unsplash.com/photo-1629273229664-11fabc0becc0?q=80&w=2062')
        ->assertSet('config_color', 'campaign')
        ->assertSet('config_button_effect', 'none')
        ->assertSee('Action')
        ->assertSee('Trigger')
        ->assertSee('Frequency')
        ->assertSee('Layout');
});

it('saves popup config including trigger, delay, frequency, layout and image url', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_title', 'Support our cause')
        ->set('config_message', 'Short message')
        ->set('config_button_text', 'Donate Now')
        ->set('config_action', 'open_campaign_page')
        ->set('config_trigger', 'on_exit')
        ->set('config_delay', 12)
        ->set('config_frequency', 'once_per_session')
        ->set('config_visibility', 'desktop_only')
        ->set('config_layout', 'full')
        ->set('config_image_url', 'https://example.com/image.jpg')
        ->set('config_color', 'blue')
        ->set('config_button_effect', 'gradient_teal_green')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element->refresh();

    expect($element->config)->toMatchArray([
        'title' => 'Support our cause',
        'message' => 'Short message',
        'button_text' => 'Donate Now',
        'action' => 'open_campaign_page',
        'trigger' => 'on_exit',
        'delay' => 12,
        'frequency' => 'once_per_session',
        'visibility' => 'desktop_only',
        'layout' => 'full',
        'image_url' => 'https://example.com/image.jpg',
        'color' => 'blue',
        'button_effect' => 'gradient_teal_green',
    ])
        ->and($element->config)->not->toHaveKey('button_color')
        ->and($element->config)->not->toHaveKey('button_size')
        ->and($element->config)->not->toHaveKey('corner_radius');
});

it('creates popup elements with default popup config', function () {
    $this->actingAs($this->user);

    Livewire::test(ElementIndex::class)
        ->call('openCreateModal')
        ->set('newType', 'popup')
        ->set('newName', 'Homepage Popup')
        ->set('newCampaignId', $this->campaign->id)
        ->call('createElement')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element = Element::query()
        ->where('name', 'Homepage Popup')
        ->firstOrFail();

    expect($element->type->value)->toBe('popup')
        ->and($element->config)->toMatchArray([
            'title' => 'Support our cause',
            'message' => 'Give waqf today. May Allah accept our waqf and bless our families with endless rewards.',
            'button_text' => 'Donate Now',
            'action' => 'checkout_modal',
            'trigger' => 'after_delay',
            'delay' => 8,
            'frequency' => 'once_per_day',
            'visibility' => 'desktop_mobile',
            'layout' => 'simple',
            'image_url' => 'https://images.unsplash.com/photo-1629273229664-11fabc0becc0?q=80&w=2062',
            'color' => 'campaign',
            'button_effect' => 'none',
        ])
        ->and(strlen($element->config['message']))->toBeLessThanOrEqual(100);
});

it('saves once per week and once per month frequency options for popups', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_frequency', 'once_per_week')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['frequency'])->toBe('once_per_week');

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_frequency', 'once_per_month')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['frequency'])->toBe('once_per_month');
});

it('saves every page load frequency option for popups', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_frequency', 'every_page_load')
        ->call('save')
        ->assertHasNoErrors();

    $element->refresh();

    expect($element->config['frequency'])->toBe('every_page_load');
});

it('reflects qr code title and message in the live preview', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::QrCode,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_title', 'Scan untuk sumbangan')
        ->set('config_message', 'Sumbangan anda membantu pembinaan surau.')
        ->assertSee('Scan untuk sumbangan')
        ->assertSee('Sumbangan anda membantu pembinaan surau.');
});

it('saves title and message config for qr code elements', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::QrCode,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_title', 'Scan untuk sumbangan')
        ->set('config_message', 'Sumbangan anda membantu.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $element->refresh();

    expect($element->config)->toMatchArray([
        'title' => 'Scan untuk sumbangan',
        'message' => 'Sumbangan anda membantu.',
    ]);
});

it('reflects popup title and message in the live preview', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_title', 'Tabung Kecemasan')
        ->set('config_message', 'Bantu mangsa banjir hari ini.')
        ->assertSee('Tabung Kecemasan')
        ->assertSee('Bantu mangsa banjir hari ini.');
});

it('reflects button size and radius in the live preview', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_size', 'large')
        ->set('config_corner_radius', 20)
        ->assertSeeInOrder([
            'padding: 16px 32px',
            'font-size: 18px',
            'border-radius: 20px',
        ]);
});

it('reflects floating button size changes in the live preview', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::FloatingButton,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_size', 'small')
        ->assertSee('padding: 8px 16px');
});

it('does not show title and message fields for form elements', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->assertSee('Button Text')
        ->assertDontSee('wire:model.live="config_title"')
        ->assertDontSee('wire:model.live="config_message"');
});

it('reflects form submit text in the live preview', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_text', 'Sumbang Sekarang')
        ->assertSee('Sumbang Sekarang');
});

it('reflects sticky button size and radius in the live preview', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::StickyButton,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('config_button_size', 'large')
        ->set('config_corner_radius', 24)
        ->assertSeeInOrder([
            'border-radius: 24px 0 0 24px',
            'width: 60px',
            'font-size: 17px',
        ]);
});

it('displays Donor Portal Button label for the default donor portal element', function () {
    $element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
        'is_donor_portal_default' => true,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->assertSee('Donor Portal Button');
});

it('shows Donor Portal Button label in the element list', function () {
    Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Button,
        'is_donor_portal_default' => true,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ElementIndex::class)
        ->assertSee('Donor Portal Button');
});
