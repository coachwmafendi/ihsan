<?php

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

it('returns element data as json for a valid floating button token', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create([
        'form_parameter' => 'FLOAT2026',
        'checkout_modal_enabled' => true,
    ]);
    $element = Element::factory()->for($org)->for($campaign)->create([
        'type' => ElementType::FloatingButton,
        'config' => [
            'text' => 'Derma Yuk',
            'position' => 'bottom_right',
            'color_mode' => 'campaign_color',
            'visibility' => 'desktop_mobile',
        ],
    ]);

    $this->getJson(route('api.public.elements.show', $element->token))
        ->assertOk()
        ->assertJson([
            'type' => 'floating_button',
            'token' => $element->token,
            'is_active' => true,
            'campaign_form_parameter' => 'FLOAT2026',
            'settings' => [
                'text' => 'Derma Yuk',
                'position' => 'bottom_right',
            ],
        ]);
});

it('returns 404 for an invalid element token', function () {
    $this->getJson(route('api.public.elements.show', 'invalid-token'))
        ->assertNotFound();
});

it('returns 404 for an inactive element', function () {
    $org = Organization::factory()->create();
    $element = Element::factory()->for($org)->create([
        'type' => ElementType::FloatingButton,
        'is_active' => false,
    ]);

    $this->getJson(route('api.public.elements.show', $element->token))
        ->assertNotFound();
});

it('includes campaign url for elements with a campaign', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create([
        'form_parameter' => 'CAMPAIGN_X',
        'checkout_modal_enabled' => true,
    ]);
    $element = Element::factory()->for($org)->for($campaign)->create([
        'type' => ElementType::FloatingButton,
    ]);

    $this->getJson(route('api.public.elements.show', $element->token))
        ->assertOk()
        ->assertJsonStructure([
            'campaign_url',
            'campaign_form_parameter',
        ]);
});

it('returns an optimized campaign image url for widget preloading', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create([
        'form_parameter' => 'IMAGE2026',
        'image_path' => 'campaigns/form-hero.jpg',
        'checkout_modal_enabled' => true,
    ]);
    $element = Element::factory()->for($org)->for($campaign)->create([
        'type' => ElementType::FloatingButton,
    ]);

    $this->getJson(route('api.public.elements.show', $element->token))
        ->assertOk()
        ->assertJsonPath(
            'campaign_image_url',
            url('/donate/campaign/IMAGE2026/image?variant=modal')
        );
});

it('returns 404 when the linked campaign is not active', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create([
        'status' => CampaignStatus::Draft,
    ]);
    $element = Element::factory()->for($org)->for($campaign)->create([
        'type' => ElementType::FloatingButton,
        'is_active' => true,
    ]);

    $this->getJson(route('api.public.elements.show', $element->token))
        ->assertNotFound();
});

it('returns element data with merged default settings for floating button', function () {
    $org = Organization::factory()->create();
    $element = Element::factory()->for($org)->create([
        'type' => ElementType::FloatingButton,
        'config' => ['text' => 'Custom'],
    ]);

    $this->getJson(route('api.public.elements.show', $element->token))
        ->assertOk()
        ->assertJsonPath('settings.text', 'Custom')
        ->assertJsonPath('settings.action', 'checkout_modal')
        ->assertJsonPath('settings.shape', 'pill');
});
