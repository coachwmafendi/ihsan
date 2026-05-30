<?php

use App\Enums\ElementType;
use App\Enums\UserRole;
use App\Filament\App\Resources\Elements\Pages\CreateElement;
use App\Filament\App\Resources\Elements\Pages\EditElement;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('shows donation form builder settings when creating a form element', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateElement::class)
        ->fillForm([
            'name' => 'Donation Form #9',
            'type' => 'form',
        ])
        ->assertSee('Donation Form Workbench')
        ->assertSee('Create element')
        ->assertSee('Cancel')
        ->assertSeeInOrder(['Show comment field', 'Cancel', 'Create element'])
        ->assertSee('Form')
        ->assertSee('Live Preview')
        ->assertSee('ihsan-builder-shell')
        ->assertSee('ihsan-builder-editor')
        ->assertSee('ihsan-builder-preview')
        ->assertSee('--cols-xl: repeat(12, minmax(0, 1fr))')
        ->assertSee('Title')
        ->assertSee('Secure donation')
        ->assertSee('Donate monthly');
});

it('shows a full page donation form workbench when editing a form element', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'name' => 'Donation Form #9',
        'token' => 'form-token-123',
        'type' => ElementType::Form,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(EditElement::class, ['record' => $element->getKey()])
        ->assertOk()
        ->assertSee('Save changes')
        ->assertSee('Cancel')
        ->assertSeeInOrder(['Show comment field', 'Cancel', 'Save changes'])
        ->assertSee('Form')
        ->assertSee('Live Preview')
        ->assertSee('ihsan-builder-shell')
        ->assertSee('ihsan-builder-editor')
        ->assertSee('ihsan-builder-preview')
        ->assertSee('--cols-xl: repeat(12, minmax(0, 1fr))')
        ->assertSee('Your most generous donation')
        ->assertSee('max-w-[380px]', false)
        ->assertSee('min-h-[420px]', false)
        ->assertSee('min-h-[360px]', false)
        ->assertSee('rounded-[42px]', false)
        ->assertSee('x-model.number="selectedAmount"', false)
        ->assertSee("frequency = 'one_time'", false)
        ->assertSee('x-on:click="submitted = true"', false)
        ->assertSee('Dedicate this donation')
        ->assertSee('Add comment')
        ->assertSee('MYR')
        ->set('data.config.title', 'Sedekah Hari Ini')
        ->assertSee('Sedekah Hari Ini');
});

it('reflects form builder controls in the live preview', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(EditElement::class, ['record' => $element->getKey()])
        ->set('data.config.title', 'Dana kilat')
        ->set('data.config.submit_text', 'Teruskan derma')
        ->set('data.config.suggested_amounts_one_time', [300, 90, 30])
        ->set('data.config.default_amount', 90)
        ->set('data.config.default_frequency', 'one_time')
        ->assertSee('Dana kilat')
        ->assertSee('Teruskan derma')
        ->assertSee('[300,90,30]', false)
        ->assertSee('selectedAmount: 90', false)
        ->assertSee("frequency: 'one_time'", false)
        ->assertSee('grid grid-cols-2 gap-2', false)
        ->assertSee('grid grid-cols-3 gap-2', false)
        ->assertSee('x-model.number="selectedAmount"', false)
        ->assertSee('Dedicate this donation')
        ->assertSee('Add comment')
        ->set('data.config.show_suggested', false)
        ->assertDontSee('grid grid-cols-3 gap-2', false)
        ->set('data.config.show_amount_input', false)
        ->assertDontSee('x-model.number="selectedAmount"', false)
        ->set('data.config.show_dedication', false)
        ->assertDontSee('Dedicate this donation')
        ->set('data.config.allow_monthly', false)
        ->assertDontSee('grid grid-cols-2 gap-2', false);
});

it('shows floating button configuration settings when creating a floating button element', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateElement::class)
        ->fillForm([
            'name' => 'Floating CTA',
            'type' => 'floating_button',
        ])
        ->assertSee('Floating Button')
        ->assertSee('Button text')
        ->assertSee('Action')
        ->assertSee('Open campaign page')
        ->assertSee('Open checkout modal')
        ->assertSee('Position & Size')
        ->assertSee('Position')
        ->assertSee('Bottom right')
        ->assertSee('Color')
        ->assertSee('Color scheme')
        ->assertSee('Show on desktop')
        ->assertSee('Show on mobile')
        ->assertSee('Icon')
        ->assertSee('Shape')
        ->assertSee('Size')
        ->assertSee('Preview')
        ->assertDontSee('Display Rules')
        ->assertDontSee('Donation Form Workbench')
        ->assertDontSee('Select a type to preview')
        ->set('data.config', [
            'button_text' => 'Support Us',
            'action' => 'checkout_modal',
            'icon' => 'heart',
            'position' => 'bottom-right',
            'size' => 'Medium',
            'shape' => 'pill',
            'color' => 'campaign',
            'visible_desktop' => true,
            'visible_mobile' => true,
        ])
        ->assertSee('Support Us');
});

it('creates a floating button element with organization and token', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateElement::class)
        ->fillForm([
            'name' => 'Floating CTA',
            'type' => 'floating_button',
            'campaign_id' => $campaign->getKey(),
        ])
        ->set('data.config', [
            'button_text' => 'Donate Now',
            'action' => 'checkout_modal',
            'position' => 'bottom-left',
            'color' => 'teal',
            'icon' => 'star',
            'shape' => 'circle',
            'size' => 'Large',
            'visible_desktop' => false,
            'visible_mobile' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $element = Element::query()
        ->where('name', 'Floating CTA')
        ->firstOrFail();

    expect($element->organization_id)->toBe($organization->getKey())
        ->and($element->campaign_id)->toBe($campaign->getKey())
        ->and($element->type->value)->toBe('floating_button')
        ->and($element->token)->toBeString()
        ->and(Str::length($element->token))->toBe(6)

        ->and($element->config)->toMatchArray([
            'button_text' => 'Donate Now',
            'action' => 'checkout_modal',
            'position' => 'bottom-left',
            'color' => 'teal',
            'icon' => 'star',
            'shape' => 'circle',
            'size' => 'Large',
            'visible_desktop' => false,
            'visible_mobile' => true,
        ]);
});

it('creates a form element with organization, token, and builder configuration', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(CreateElement::class)
        ->fillForm([
            'name' => 'Donation Form #9',
            'type' => 'form',
            'campaign_id' => $campaign->getKey(),
        ])
        ->set('data.config', [
            'template' => 'secure-donation',
            'title' => 'Your most generous donation',
            'text_color' => '#212830',
            'background_color' => '#FFFFFF',
            'icon_color' => '#FF435A',
            'border_size' => 2,
            'border_radius' => 6,
            'border_color' => '#DEDFF3',
            'show_shadow' => false,
            'allow_monthly' => true,
            'show_dedication' => true,
            'submit_text' => 'Donate and Support',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $element = Element::query()
        ->where('name', 'Donation Form #9')
        ->firstOrFail();

    expect($element->organization_id)->toBe($organization->getKey())
        ->and($element->campaign_id)->toBe($campaign->getKey())
        ->and($element->type->value)->toBe('form')
        ->and($element->token)->toBeString()
        ->and(Str::length($element->token))->toBe(6)
        ->and($element->config)->toMatchArray([
            'template' => 'secure-donation',
            'title' => 'Your most generous donation',
            'text_color' => '#212830',
            'background_color' => '#FFFFFF',
            'icon_color' => '#FF435A',
            'border_size' => 2,
            'border_radius' => 6,
            'border_color' => '#DEDFF3',
            'show_shadow' => false,
            'allow_monthly' => true,
            'show_dedication' => true,
            'submit_text' => 'Donate and Support',
        ]);
});
