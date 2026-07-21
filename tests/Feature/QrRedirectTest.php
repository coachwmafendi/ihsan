<?php

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

it('renders the qr redirect page with an auto-trigger widget', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'qr-auto-token',
        'type' => ElementType::QrCode,
        'is_active' => true,
    ]);

    $response = $this->get(route('qr.redirect', $element));

    $response->assertOk()
        ->assertViewIs('qr.redirect')
        ->assertSee('data-auto-trigger="true"', false)
        ->assertSee('data-token="qr-auto-token"', false)
        ->assertSee('/e/widget.js', false);
});

it('returns 404 for an inactive element', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'qr-inactive-token',
        'type' => ElementType::QrCode,
        'is_active' => false,
    ]);

    $this->get(route('qr.redirect', $element))->assertNotFound();
});

it('returns 404 for an inactive campaign', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Draft,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'qr-draft-campaign-token',
        'type' => ElementType::QrCode,
        'is_active' => true,
    ]);

    $this->get(route('qr.redirect', $element))->assertNotFound();
});
