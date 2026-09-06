<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

/**
 * The picker was a 20px tall button with no state exposed to assistive tech:
 * below the WCAG 2.5.8 target size, unreachable by keyboard beyond a tab, and
 * silent about whether it was open.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr', 'usd', 'sgd']],
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Form,
    ]);
});

it('exposes the picker state to assistive technology', function () {
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('aria-haspopup="listbox"', false)
        ->assertSee('role="listbox"', false)
        ->assertSee('role="option"', false)
        ->assertSee('aria-expanded', false)
        ->assertSee('Change currency', false);
});

it('gives the picker a reachable target size', function () {
    // min-h-11 is 44px, the size a thumb and WCAG 2.5.8 both want.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('min-h-11 cursor-pointer select-none items-center', false)
        ->assertSee('min-h-11 w-full items-center', false);
});

it('closes on escape and walks the list with the arrow keys', function () {
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('keydown.escape.stop', false)
        ->assertSee('keydown.down.prevent', false)
        ->assertSee('keydown.up.prevent', false);
});

it('switches the currency in the browser before the server answers', function () {
    // choose() sets the symbol locally, then tells the server, which sends the
    // suggested amounts back on currency-updated.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('currencySymbol = symbol', false)
        ->assertSee('$wire.selectCurrency(code)', false)
        ->assertDontSee('wire:click="selectCurrency', false);
});
