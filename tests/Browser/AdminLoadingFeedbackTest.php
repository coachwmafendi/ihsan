<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The spinners themselves, in a browser.
 *
 * The markup test next to this one proves the wiring ships. It cannot prove
 * what the wiring does: wire:loading elements are hidden by Livewire at
 * runtime, and a spinner pointed at the wrong target is either always on or
 * never on while looking identical in the HTML.
 */
beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    Organization::factory()->create();
});

/**
 * Whether an element is being shown, read the way the browser sees it rather
 * than from the class list.
 */
function displayOfSpinner(string $selector): string
{
    return <<<JS
        (() => {
            const el = document.querySelector('{$selector}');
            return el ? getComputedStyle(el).display : 'NOT FOUND';
        })()
    JS;
}

it('keeps the transactions spinner out of sight until the filter runs', function () {
    $this->actingAs($this->admin);

    visit('/admin/transactions')
        ->assertScript(displayOfSpinner('[wire\\\\:target="applyFilters"][data-loading-spinner]'), 'none');
});

it('keeps the revenue figures at full strength until a period is asked for', function () {
    // The dimming class is applied by Livewire during the request, so finding
    // it on a page at rest would mean it never comes off.
    $this->actingAs($this->admin);

    visit('/admin/revenue')
        ->assertScript(<<<'JS'
            (() => {
                const el = document.querySelector('[wire\\:loading\\.class\\.delay\\.default]');
                return el ? String(el.classList.contains('opacity-40')) : 'NOT FOUND';
            })()
        JS, 'false');
});
