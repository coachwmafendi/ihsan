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
 * The remedy is an option the property can actually match. This walks every
 * bound select and fails on any that has only the placeholder to fall back to.
 */
it('gives every bound select an option its property can match', function () {
    $offenders = [];

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

        foreach ($matches as $match) {
            [$whole, $body] = $match;

            if (! str_contains($whole, 'placeholder=')) {
                continue;
            }

            if (str_contains($body, 'value=""')) {
                continue;
            }

            $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([]);
});
