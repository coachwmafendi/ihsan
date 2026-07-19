<?php

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Support\EmbedWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

it('renders a static button with campaign checkout url', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
        'form_parameter' => 'RAMADAN2026',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'btn-static',
        'type' => ElementType::Button,
        'config' => [
            'button_text' => 'Donate Now',
            'button_color' => 'bg-green-600 hover:bg-green-700',
            'button_size' => 'text-base px-6 py-3',
            'corner_radius' => 12,
            'button_icon' => 'heart',
            'alignment' => 'center',
            'action' => 'checkout_modal',
        ],
    ]);

    $html = EmbedWidget::staticButtonHtml($element);

    expect($html)
        ->toContain('btn-static')
        ->toContain('Donate Now')
        ->toContain('ihsan-button')
        ->toContain('data-ihsan-token="btn-static"')
        ->toContain('/checkout/RAMADAN2026?popup=1')
        ->toContain('background:#16a34a')
        ->toContain('border-radius:12px')
        ->toContain('cursor:pointer !important')
        ->not->toContain('ihsan-embed-label')
        ->not->toContain('Ihsan Button —');
});

it('renders fallback donation url when checkout modal is disabled', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => false,
        'form_parameter' => 'RAMADAN2026',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'btn-fallback',
        'type' => ElementType::Button,
    ]);

    $html = EmbedWidget::staticButtonHtml($element);

    expect($html)->toContain('/donate/btn-fallback?popup=1');
    expect($html)->not->toContain('/checkout/RAMADAN2026');
});

it('encodes the popup checkout url in the qr code', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'qr-popup',
        'type' => ElementType::QrCode,
    ]);

    $html = EmbedWidget::staticQrCodeHtml($element);

    expect($html)->toContain(urlencode(url('/donate/qr-popup?popup=1')));
});

it('escapes html in button text', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Button,
        'config' => ['button_text' => '<script>alert(1)</script>'],
    ]);

    $html = EmbedWidget::staticButtonHtml($element);

    expect($html)->not->toContain('<script>alert(1)</script>');
    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});
