<?php

use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

it('renders donor inputs at 16px on mobile to prevent iOS focus zoom', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element->token))->assertOk();

    expect(substr_count($response->getContent(), 'text-base outline-none sm:text-sm'))
        ->toBeGreaterThanOrEqual(4)
        ->and($response->getContent())
        ->not->toContain('bg-white px-3 text-sm outline-none');
});

it('shows the amount summary bar on both details and payment steps', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element->token))->assertOk();

    expect(substr_count($response->getContent(), "frequency === 'monthly' ? 'Monthly' : 'One-time'"))
        ->toBe(2);
});

it('does not animate step containers on leave, keeping one step in the layout at a time', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->get(route('donations.show', $element->token))
        ->assertOk()
        ->assertDontSee('opacity-0 -translate-y-2', false);
});

it('forces the Stripe payment element to render in English', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->get(route('donations.show', $element->token))
        ->assertOk()
        ->assertSee("locale: 'en'", false)
        ->assertDontSee("locale: 'ms'", false);
});

it('keeps suggested amount chips on a single line', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->get(route('donations.show', $element->token))
        ->assertOk()
        ->assertSee('whitespace-nowrap rounded-lg border', false);
});

it('renders the fee cover tooltip through the shared viewport-clamped component', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element->token))->assertOk();

    expect($response->getContent())
        ->toContain('you help cover essential software and payment processing fees')
        ->toContain('uiTooltip(')
        ->not->toContain('style="right: -0.75rem;"');
});
