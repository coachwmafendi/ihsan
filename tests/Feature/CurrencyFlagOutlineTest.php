<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/**
 * Every flag we render has white touching an edge - Singapore's lower half,
 * the American and Malaysian stripes - so on the white checkout the flag lost
 * its shape. The outline lives inside the SVG rather than as a CSS ring: the
 * three flags have different aspect ratios and sit letterboxed inside a shared
 * box, so a ring would float away from the artwork.
 */
it('draws an outline inside every flag', function (string $currency) {
    $html = Blade::render('<x-currency-flag :currency="$currency" />', ['currency' => $currency]);

    expect($html)->toContain('vector-effect="non-scaling-stroke"')
        // non-scaling-stroke reads the width in screen pixels, so a fraction
        // of a viewBox unit would render as nothing at all.
        ->and($html)->toContain('stroke-width="1"');
})->with(['myr', 'usd', 'sgd']);

it('keeps the outline subtle enough not to read as a border', function () {
    expect(Blade::render('<x-currency-flag currency="sgd" />'))
        ->toContain('rgba(15,23,42,0.28)');
});

/**
 * The three flags have different real-world ratios - Malaysia 2:1, the United
 * States 1.9:1, Singapore 3:2 - so drawn true to life they sat letterboxed at
 * different heights inside a shared box and the row looked ragged. Cropping
 * the fly end brings them to a single 3:2, which is Singapore's own ratio.
 */
it('draws every flag at the same 3:2 ratio', function (string $currency, string $viewBox) {
    expect(Blade::render('<x-currency-flag :currency="$currency" />', ['currency' => $currency]))
        ->toContain('viewBox="'.$viewBox.'"');
})->with([
    ['myr', '0 0 20 13.333'],
    ['usd', '0 0 15 10'],
    ['sgd', '0 0 3 2'],
]);
