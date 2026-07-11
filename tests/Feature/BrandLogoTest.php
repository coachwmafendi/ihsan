<?php

use Illuminate\Support\Facades\Blade;

it('renders the eight-point star mark in app-logo-icon', function () {
    $html = Blade::render('<x-app-logo-icon class="h-7 w-auto" />');

    expect($html)
        ->toContain('rotate(45 32 32)')
        ->toContain('stroke="currentColor"')
        ->toContain('text-teal-600')
        ->toContain('dark:text-teal-400')
        ->not->toContain('M32 2L37 27');
});

it('merges caller classes onto app-logo-icon', function () {
    $html = Blade::render('<x-app-logo-icon class="h-7 w-auto" />');

    expect($html)->toContain('h-7 w-auto');
});
