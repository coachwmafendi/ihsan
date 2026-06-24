<?php

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

it('renders a styled button iframe for an active button element', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
        'form_parameter' => 'RAMADAN2026',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'btn123',
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

    $response = $this->get(route('embed.button', ['token' => $element->token]));

    $response->assertOk();
    $base = config('app.url');

    $response->assertSee('Donate Now', false);
    $response->assertSee($base.'/checkout/RAMADAN2026', false);
    $response->assertSee('background: #16a34a', false);
    $response->assertSee('border-radius: 12px', false);
});

it('returns 404 for inactive elements', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'inactive-btn',
        'type' => ElementType::Button,
        'is_active' => false,
    ]);

    $this->get(route('embed.button', ['token' => $element->token]))->assertNotFound();
});

it('returns 404 for non-existent tokens', function () {
    $this->get(route('embed.button', ['token' => 'does-not-exist']))->assertNotFound();
});

it('falls back to campaign donation url when checkout modal is disabled', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => false,
        'form_parameter' => 'RAMADAN2026',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'btn456',
        'type' => ElementType::Button,
    ]);

    $this->get(route('embed.button', ['token' => $element->token]))
        ->assertOk()
        ->assertSee(config('app.url').'/donate/btn456', false)
        ->assertDontSee('/checkout/', false);
});
