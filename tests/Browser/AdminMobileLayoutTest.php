<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Admin pages on a phone, measured rather than eyeballed.
 *
 * Filament's layout wrapper is `overflow-x: clip`, so anything wider than the
 * viewport is not scrolled to — it is cut off and gone. A period button or a
 * donation figure can be missing on a phone while the markup looks perfect.
 */
beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    Organization::factory()->count(3)->create();
});

/**
 * How far the page reaches past the phone's screen, in pixels.
 */
const OVERFLOW_PAST_VIEWPORT = <<<'JS'
    (() => {
        const layout = document.querySelector('.fi-layout');
        return layout ? layout.scrollWidth - layout.clientWidth : -1;
    })()
JS;

it('keeps the platform overview inside the width of a phone', function () {
    $this->actingAs($this->admin);

    visit('/admin/platform-overview')
        ->on()->mobile()
        ->assertScript(OVERFLOW_PAST_VIEWPORT, 0);
});

it('keeps every revenue period button inside the width of a phone', function () {
    $this->actingAs($this->admin);

    visit('/admin/revenue')
        ->on()->mobile()
        ->assertScript(OVERFLOW_PAST_VIEWPORT, 0);
});

it('fits the overview metric cards two to a row on a phone', function () {
    // Seven full-width cards cost more than a screen of scrolling before the
    // first real section. Two to a row halves that.
    $this->actingAs($this->admin);

    visit('/admin/platform-overview')
        ->on()->mobile()
        ->assertScript(<<<'JS'
            (() => {
                const cards = [...document.querySelectorAll('.ihsan-admin-metric-card')];
                if (! cards.length) return 'NO CARDS';
                const tops = new Set(cards.map((c) => Math.round(c.getBoundingClientRect().top)));
                return cards.length > tops.size ? 'two up' : 'one up';
            })()
        JS, 'two up')
        ->assertScript(OVERFLOW_PAST_VIEWPORT, 0);
});

it('gives organization edit fields the full width of a phone', function () {
    // The vertical tab rail is a fixed 192px. Left beside the form on a phone
    // it leaves the inputs about 90px wide — too narrow to read or type in.
    $this->actingAs($this->admin);

    $organization = Organization::factory()->create();

    visit("/admin/organizations/{$organization->getKey()}/edit")
        ->on()->mobile()
        ->assertScript(<<<'JS'
            (() => {
                const input = document.querySelector('input.fi-input');
                return input ? Math.round(input.getBoundingClientRect().width) > 240 : 'NO INPUT';
            })()
        JS, true)
        ->assertScript(OVERFLOW_PAST_VIEWPORT, 0);
});

it('shows a bottom navigation bar on a phone and marks the page you are on', function () {
    $this->actingAs($this->admin);

    visit('/admin/transactions')
        ->on()->mobile()
        ->assertVisible('@admin-bottom-nav')
        ->assertScript(<<<'JS'
            (() => {
                const bar = document.querySelector('[data-test="admin-bottom-nav"]');
                if (! bar) return 'NO BAR';
                const labels = [...bar.querySelectorAll('a, button')].map((el) => el.textContent.trim());
                const current = bar.querySelector('[aria-current="page"]');
                return labels.join('|') + ' :: ' + (current ? current.textContent.trim() : 'NONE');
            })()
        JS, 'Overview|Transactions|Organizations|Revenue|More :: Transactions');
});

it('opens the sidebar from the bottom bar so the other pages stay reachable', function () {
    $this->actingAs($this->admin);

    visit('/admin/transactions')
        ->on()->mobile()
        ->click('More')
        ->assertSee('Fraud Prevention')
        ->assertSee('Monthly Invoices')
        // The bar would otherwise sit on top of the open sidebar.
        ->assertScript(<<<'JS'
            (() => {
                const bar = document.querySelector('[data-test="admin-bottom-nav"]');
                return bar ? getComputedStyle(bar).display : 'NO BAR';
            })()
        JS, 'none');
});

it('keeps the bottom navigation bar off a desktop screen', function () {
    $this->actingAs($this->admin);

    visit('/admin/transactions')
        ->assertScript(<<<'JS'
            (() => {
                const bar = document.querySelector('[data-test="admin-bottom-nav"]');
                return bar ? getComputedStyle(bar).display : 'NO BAR';
            })()
        JS, 'none');
});

it('lets a phone reach the last period on the revenue switch', function () {
    // The switch is wider than a phone either way. What matters is that the
    // buttons past the fold can be scrolled to and pressed rather than clipped.
    $this->actingAs($this->admin);

    visit('/admin/revenue')
        ->on()->mobile()
        ->assertScript(<<<'JS'
            (() => {
                const bar = document.querySelector('.ihsan-admin-segmented');
                return bar ? getComputedStyle(bar).overflowX : 'NOT FOUND';
            })()
        JS, 'auto')
        ->click('All Time')
        ->assertSee('Total processing fees');
});
