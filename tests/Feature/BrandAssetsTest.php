<?php

it('ships the eight-point star mark in public svg assets', function (string $file) {
    $svg = file_get_contents(public_path($file));

    expect($svg)
        ->toContain('rotate(45 32 32)')
        ->not->toContain('M32 2L37 27');
})->with(['logo-ihsan.svg', 'logo-ihsan-dark.svg', 'favicon.svg']);

it('uses dark-mode teal in the dark lockup', function () {
    expect(file_get_contents(public_path('logo-ihsan-dark.svg')))
        ->toContain('#2dd4bf');
});

it('omits the center dot in the small favicon svg', function () {
    expect(file_get_contents(public_path('favicon.svg')))
        ->not->toContain('<circle');
});
