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
        ->assertSee('Design')
        ->assertSee('Behavior')
        ->assertSee('Embed')
        ->assertSee('Live Preview')
        ->assertSee('ihsan-builder-shell')
        ->assertSee('ihsan-builder-editor')
        ->assertSee('ihsan-builder-preview')
        ->assertSee('--cols-xl: repeat(12, minmax(0, 1fr))')
        ->assertSee('Title')
        ->assertSee('Text color')
        ->assertSee('Background color')
        ->assertSee('Icon color')
        ->assertSee('Border size')
        ->assertSee('Border radius')
        ->assertSee('Border color')
        ->assertSee('Show shadow')
        ->assertSee('Available after saving this element.')
        ->assertSee('Your most generous donation')
        ->assertSee('Donate and Support');
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
        ->assertSee('Design')
        ->assertSee('Behavior')
        ->assertSee('Embed')
        ->assertSee('Live Preview')
        ->assertSee('HTML Code')
        ->assertSee('ihsan-builder-shell')
        ->assertSee('ihsan-builder-editor')
        ->assertSee('ihsan-builder-preview')
        ->assertSee('--cols-xl: repeat(12, minmax(0, 1fr))')
        ->assertSee('http://ihsan.test/donate/form-token-123')
        ->assertSee('<iframe src="http://ihsan.test/donate/form-token-123"')
        ->assertSee('Your most generous donation')
        ->assertSee('max-w-[380px]', false)
        ->assertSee('min-h-[720px]', false)
        ->assertSee('min-h-[690px]', false)
        ->assertSee('min-h-[610px]', false)
        ->assertSee('rounded-[42px]', false)
        ->assertSee('data-preview-phone', false)
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
        ->assertSee('data-preview-frequency', false)
        ->assertSee('data-preview-suggested', false)
        ->assertSee('data-preview-amount-input', false)
        ->assertSee('data-preview-dedication', false)
        ->assertSee('data-preview-comment', false)
        ->set('data.config.show_suggested', false)
        ->assertDontSee('data-preview-suggested', false)
        ->set('data.config.show_amount_input', false)
        ->assertDontSee('data-preview-amount-input', false)
        ->set('data.config.show_dedication', false)
        ->assertDontSee('data-preview-dedication', false)
        ->set('data.config.show_comment', false)
        ->assertDontSee('data-preview-comment', false)
        ->set('data.config.allow_monthly', false)
        ->assertDontSee('data-preview-frequency', false);
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
            'config' => [
                'title' => 'Your most generous donation',
                'text_color' => '#212830',
                'background_color' => '#FFFFFF',
                'icon_color' => '#FF435A',
                'border_size' => 2,
                'border_radius' => 6,
                'border_color' => '#DEDFF3',
                'show_shadow' => false,
                'suggested_amounts_one_time' => [500, 200, 100, 50, 40, 30],
                'suggested_amounts_monthly' => [100, 50, 30, 20, 10, 5],
                'default_amount' => 5,
                'default_frequency' => 'monthly',
                'allow_monthly' => true,
                'show_dedication' => true,
                'show_comment' => true,
                'submit_text' => 'Donate and Support',
            ],
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
        ->and(Str::length($element->token))->toBe(32)
        ->and($element->config)->toMatchArray([
            'title' => 'Your most generous donation',
            'text_color' => '#212830',
            'background_color' => '#FFFFFF',
            'icon_color' => '#FF435A',
            'border_size' => 2,
            'border_radius' => 6,
            'border_color' => '#DEDFF3',
            'show_shadow' => false,
            'suggested_amounts_one_time' => [500, 200, 100, 50, 40, 30],
            'suggested_amounts_monthly' => [100, 50, 30, 20, 10, 5],
            'default_amount' => 5,
            'default_frequency' => 'monthly',
            'allow_monthly' => true,
            'show_dedication' => true,
            'show_comment' => true,
            'submit_text' => 'Donate and Support',
        ]);
});
