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
        ->toContain('class=\u0022ihsan-button\u0022')
        ->toContain('data-ihsan-token=\u0022btn-verify\u0022')
        ->toContain('data-enhance=\u0022true\u0022')
        ->toContain('Donate Now');
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
        ->not->toContain('class=\u0022ihsan-button\u0022')
        ->not->toContain('data-enhance=\u0022true\u0022')
        ->toContain('data-token=\u0022popup-verify\u0022');
});
