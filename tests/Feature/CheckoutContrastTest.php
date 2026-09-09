<?php

declare(strict_types=1);

/**
 * Four pieces of text in the checkout sat under the contrast WCAG asks for, and
 * two of them carried the things that matter most: the sentence explaining why
 * the total is higher than the amount chosen, and the button the whole screen
 * exists to get pressed.
 *
 * Measured in a browser against the real backgrounds, not against white - the
 * selected amount sits on a tinted pill, and judging it against the page put
 * it a full point away from its true reading.
 */
it('keeps the faded greys out of the checkout', function () {
    // slate-400 is 2.56:1 on white and 2.45:1 on the tinted boxes. Nothing that
    // has to be read belongs in it.
    $views = [
        'resources/views/livewire/donation-form.blade.php',
        'resources/views/components/checkout-help.blade.php',
    ];

    foreach ($views as $view) {
        expect(file_get_contents(base_path($view)))
            ->not->toContain('text-slate-400', "{$view} still has text at 2.5:1");
    }
});

it('darkens the selected amount enough to read against its own pill', function () {
    // teal-700 on teal-200 measured 4.27:1 - close, and still short.
    $markup = file_get_contents(base_path('resources/views/livewire/donation-form.blade.php'));

    expect($markup)
        ->toContain('bg-teal-200 text-teal-800')
        ->not->toContain('bg-teal-200 text-teal-700');
});

it('gives the donate button a background its white text can survive', function () {
    // White on teal-600 is 3.67:1; on teal-700 it is 5.36:1.
    $markup = file_get_contents(base_path('resources/views/livewire/donation-form.blade.php'));

    expect($markup)
        ->toContain('bg-teal-700 hover:bg-teal-800')
        ->not->toContain('bg-teal-600 hover:bg-teal-700');
});
