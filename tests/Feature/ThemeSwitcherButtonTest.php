<?php

use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

test('user menu dropdown only uses a medium panel width on the app panel', function () {
    $blade = File::get(resource_path('views/vendor/filament-panels/components/user-menu.blade.php'));

    expect($blade)->toContain(':width="filament()->getId() === \'app\' ? \'md\' : null"');
});

test('theme switcher button exposes obvious active and inactive states', function () {
    $html = Blade::render(
        <<<'BLADE'
        <x-filament-panels::theme-switcher.button :icon="$icon" theme="dark" />
        BLADE,
        ['icon' => Heroicon::Moon],
    );

    expect($html)
        ->toContain('x-bind:aria-pressed="theme === \'dark\'"')
        ->toContain('x-bind:class="{ \'fi-active\': theme === \'dark\' }"')
        ->toContain('fi-theme-switcher-btn transition duration-150');
});

test('theme switcher styles render large selected pill controls', function () {
    $css = File::get(resource_path('css/filament/app/theme.css'));

    expect($css)
        ->toContain('.fi-theme-switcher {')
        ->toContain('grid-template-columns: repeat(3, minmax(0, 1fr))')
        ->toContain('.fi-theme-switcher .fi-theme-switcher-btn {')
        ->toContain('min-height: calc(var(--spacing) * 12)')
        ->toContain('border-color: color-mix(in oklab, var(--primary-500) 70%, transparent)')
        ->not->toContain('.fi-theme-switcher .fi-theme-switcher-btn .fi-icon');
});
