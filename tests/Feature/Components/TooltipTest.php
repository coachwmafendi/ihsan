<?php

use Illuminate\Support\Facades\Blade;

it('renders tooltip text in a body with role tooltip', function () {
    $html = Blade::render('<x-ui.tooltip text="Hello world"><x-heroicon-o-user class="size-4" /></x-ui.tooltip>');

    expect($html)
        ->toContain('Hello world')
        ->toContain('role="tooltip"')
        ->toContain('tabindex="0"')
        ->toContain('role="img"')
        ->toContain('aria-label="Hello world"');
});

it('renders the tip slot html unescaped', function () {
    $html = Blade::render(<<<'BLADE'
        <x-ui.tooltip>
            <x-heroicon-o-question-mark-circle class="size-4" />
            <x-slot:tip>Platform <strong>fee</strong></x-slot:tip>
        </x-ui.tooltip>
    BLADE);

    expect($html)
        ->toContain('Platform <strong>fee</strong>')
        ->toContain('role="tooltip"')
        ->not->toContain('&lt;strong&gt;')
        ->not->toContain('&gt;');
});

it('does not double-wrap interactive triggers and links the tooltip aria', function () {
    $html = Blade::render('<x-ui.tooltip text="Save"><button type="button">Save</button></x-ui.tooltip>');

    expect($html)
        ->toContain('role="tooltip"')
        ->toContain(':aria-describedby="open ? tipId : null"')
        ->not->toContain('tabindex="0"')
        ->not->toContain('role="img"');
});

it('does not render the tooltip body when disabled', function () {
    $html = Blade::render('<x-ui.tooltip text="Hidden" disabled><x-heroicon-o-user class="size-4" /></x-ui.tooltip>');

    expect($html)
        ->not->toContain('role="tooltip"')
        ->not->toContain('x-teleport')
        ->not->toContain('tabindex="0"')
        ->not->toContain('role="img"');
});
