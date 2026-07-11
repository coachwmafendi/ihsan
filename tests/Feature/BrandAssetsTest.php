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

it('ships raster favicons at the correct dimensions', function (string $file, int $size) {
    [$width, $height] = getimagesize(public_path($file));

    expect($width)->toBe($size)
        ->and($height)->toBe($size);
})->with([
    ['favicon-32x32.png', 32],
    ['favicon-180x180.png', 180],
    ['apple-touch-icon.png', 180],
]);

it('ships a two-frame favicon ico', function () {
    $bytes = file_get_contents(public_path('favicon.ico'));

    expect(substr($bytes, 0, 4))->toBe("\x00\x00\x01\x00")
        ->and(unpack('v', substr($bytes, 4, 2))[1])->toBe(2);
});
