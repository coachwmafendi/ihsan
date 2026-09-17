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
