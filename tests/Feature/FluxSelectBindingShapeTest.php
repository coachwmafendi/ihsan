<?php

declare(strict_types=1);

/**
 * Flux renders a select's placeholder as
 *
 *     <option value="" disabled selected class="placeholder">…</option>
 *
 * with the selected attribute written into the markup the server sends. A bound
 * property with no option matching its value leaves the browser on that option,
 * and the refund modal submitted an empty reason while showing the organiser
 * the one they had picked.
 *
 * The remedy is an option the property can actually match - and then no
 * placeholder attribute beside it, because the two together list the same
 * words twice and the country select offered "Country" as a country.
 *
 * @return array<int, array{file: string, whole: string, body: string}>
 */
function boundFluxSelects(): array
{
    $selects = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getPathname(), '.blade.php')) {
            continue;
        }

        $markup = file_get_contents($file->getPathname());

        // Each bound select, from its opening tag to its closing one.
        preg_match_all('/<flux:select\b[^>]*wire:model[^>]*>(.*?)<\/flux:select>/s', $markup, $matches, PREG_SET_ORDER);

        foreach ($matches as [$whole, $body]) {
            $selects[] = [
                'file' => str_replace(base_path().'/', '', $file->getPathname()),
                'whole' => $whole,
                'body' => $body,
            ];
        }
    }

    return $selects;
}

it('gives every bound select an option its property can match', function () {
    // A placeholder is what leaves a bound property with nothing to match, so
    // that is the shape this rule is about. Plenty of selects are bound to a
    // property that always holds one of the listed values and need no empty
    // option at all.
    $offenders = collect(boundFluxSelects())
        ->filter(fn (array $select): bool => str_contains($select['whole'], 'placeholder='))
        ->reject(fn (array $select): bool => str_contains($select['body'], 'value=""'))
        ->pluck('file')
        ->unique()
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

it('does not let a bound select show its empty option twice', function () {
    // The placeholder is an empty option of Flux's own making, so beside a real
    // one the list opens with the same words on two rows - and the second reads
    // as a choice rather than a prompt.
    $offenders = collect(boundFluxSelects())
        ->filter(fn (array $select): bool => str_contains($select['whole'], 'placeholder='))
        ->pluck('file')
        ->unique()
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
