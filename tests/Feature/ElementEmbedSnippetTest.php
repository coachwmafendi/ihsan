<?php

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Support\Facades\Blade;

it('renders static button and enhancement script for button elements', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
        'form_parameter' => 'BUTTONTEST',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'btn-verify',
        'type' => ElementType::Button,
        'config' => [
            'button_text' => 'Donate Now',
            'button_color' => 'bg-green-600',
        ],
    ]);

    $html = Blade::render(
        file_get_contents(resource_path('views/filament/forms/components/element-embed-snippet.blade.php')),
        ['element' => $element]
    );

    expect($html)
        ->toContain('ihsan-button')
        ->toContain('data-ihsan-token')
        ->toContain('data-enhance')
        ->toContain('Donate Now')
        ->not->toContain('ihsan-embed-label');
});

it('renders only script widget for non-button elements', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'popup-verify',
        'type' => ElementType::Popup,
    ]);

    $html = Blade::render(
        file_get_contents(resource_path('views/filament/forms/components/element-embed-snippet.blade.php')),
        ['element' => $element]
    );

    expect($html)
        ->not->toContain('ihsan-button')
        ->not->toContain('data-enhance')
        ->toContain('popup-verify');
});

it('renders a visible static link embed for link elements', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'link-verify',
        'type' => ElementType::Link,
        'config' => [
            'text' => 'Donate Today',
            'button_color' => 'bg-teal-600 hover:bg-teal-700',
            'button_size' => 'large',
            'corner_radius' => 12,
            'button_icon' => 'heart',
            'alignment' => 'center',
            'action' => 'checkout_modal',
        ],
    ]);

    $html = Blade::render(
        file_get_contents(resource_path('views/filament/forms/components/element-embed-snippet.blade.php')),
        ['element' => $element]
    );

    expect($html)
        ->toContain('ihsan-link')
        ->toContain('data-ihsan-token')
        ->toContain('data-enhance')
        ->toContain('Donate Today')
        ->toContain('background:#0d9488');
});

it('renders a visible placeholder for floating button elements', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'float-verify',
        'type' => ElementType::FloatingButton,
        'config' => [
            'button_text' => 'Give Now',
            'position' => 'bottom-right',
        ],
    ]);

    $html = Blade::render(
        file_get_contents(resource_path('views/filament/forms/components/element-embed-snippet.blade.php')),
        ['element' => $element]
    );

    expect($html)
        ->toContain('ihsan-embed-placeholder')
        ->toContain('ihsan-floating-button-placeholder')
        ->toContain('data-ihsan-token')
        ->toContain('data-enhance')
        ->toContain('Ihsan Floating Button')
        ->toContain('Give Now')
        ->toContain('Bottom right corner');
});

it('renders a visible static qr code embed for qr code elements', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'qr-verify',
        'type' => ElementType::QrCode,
        'config' => [
            'label' => 'Scan to donate',
            'size' => 'large',
            'alignment' => 'center',
        ],
    ]);

    $html = Blade::render(
        file_get_contents(resource_path('views/filament/forms/components/element-embed-snippet.blade.php')),
        ['element' => $element]
    );

    expect($html)
        ->toContain('ihsan-embed-placeholder')
        ->toContain('ihsan-qr-code-placeholder')
        ->toContain('data-ihsan-token')
        ->toContain('data-enhance')
        ->toContain('Scan to donate')
        ->toContain('api.qrserver.com')
        ->toContain('250x250');
});
