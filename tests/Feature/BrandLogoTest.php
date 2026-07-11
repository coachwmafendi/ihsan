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

it('renders a lowercase teal wordmark in app-logo', function () {
    $html = Blade::render('<x-app-logo />');

    expect($html)
        ->toContain('ihsan')
        ->toContain('[&amp;&gt;div:last-child]:text-teal-700')
        ->toContain('dark:[&amp;&gt;div:last-child]:text-teal-400');
});

it('renders the teal wordmark in the sidebar variant', function () {
    $html = Blade::render('<x-app-logo :sidebar="true" />');

    expect($html)
        ->toContain('ihsan')
        ->toContain('[&amp;&gt;div:last-child]:text-teal-700');
});

it('shows the brand wordmark next to the mark on the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('>ihsan</span>', false);
});

it('does not force fill-current on the auth layout logos', function (string $layout) {
    $blade = file_get_contents(resource_path("views/layouts/auth/{$layout}.blade.php"));

    expect($blade)
        ->not->toContain('fill-current');
})->with(['simple', 'split', 'card']);
