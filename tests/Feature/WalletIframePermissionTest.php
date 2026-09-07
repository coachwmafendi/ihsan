<?php

declare(strict_types=1);

/**
 * The checkout modal is a cross-origin iframe: app.getihsan.my inside the
 * organisation's own site. Safari refuses Apple Pay in a cross-origin iframe
 * unless it carries allow="payment", so every place that builds the frame has
 * to set it or the wallet silently never appears.
 */
it('lets the embedded checkout ask for payment permission', function (string $path) {
    expect(file_get_contents(base_path($path)))->toContain('payment *');
})->with([
    'resources/js/widget.js',
    'resources/js/loader.js',
    'resources/views/filament/forms/components/element-embed-snippet.blade.php',
]);

it('sets the permission on the modal frame the snippet builds', function () {
    $snippet = file_get_contents(base_path('resources/views/filament/forms/components/element-embed-snippet.blade.php'));

    expect($snippet)->toContain('data-ihsan-frame allow="payment *"');
});
