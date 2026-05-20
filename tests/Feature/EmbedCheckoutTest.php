<?php

use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

it('serves the embed script as javascript', function () {
    $this->get(route('embed.script'))
        ->assertOk()
        ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
        ->assertSee('IhsanCheckout', false)
        ->assertSee('data-ihsan-form', false)
        ->assertSee('/checkout', false);
});

it('redirects an allowed embed checkout request to the hosted donation form', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => true,
        'checkout_allowed_domains' => ['mumzatuttaqwa.com'],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'form-token-123',
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/ms/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertRedirect(route('donations.show', ['element' => $element->token, 'embed' => 1]));
});

it('rejects embed checkout requests from domains outside the campaign allowlist', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => true,
        'checkout_allowed_domains' => ['mumzatuttaqwa.com'],
    ]);
    Element::factory()->for($organization)->for($campaign)->create([
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://example.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertForbidden();
});

it('rejects checkout requests when modal checkout is disabled', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => false,
        'checkout_allowed_domains' => ['mumzatuttaqwa.com'],
    ]);
    Element::factory()->for($organization)->for($campaign)->create([
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertNotFound();
});
