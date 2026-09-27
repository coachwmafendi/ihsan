<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * checkout.css is built with `source(none)` and an explicit list, so Tailwind
 * only keeps the classes it finds in the files named there. The campaign page
 * shares a layout with the donation form but was never on the list, so every
 * class only it used was purged and the page rendered unstyled - a full-width
 * logo and no cards - with nothing failing to say so.
 */
it('builds the checkout stylesheet from every page rendered through its layouts', function () {
    $css = file_get_contents(resource_path('css/checkout.css'));

    $pages = collect(File::allFiles(app_path('Livewire')))
        ->map(fn ($file): array => ['name' => $file->getFilename(), 'code' => file_get_contents($file->getPathname())])
        ->filter(fn (array $file): bool => preg_match('/layouts\.(donation|popup|embed)/', $file['code']) === 1);

    expect($pages)->not->toBeEmpty();

    $unreadViews = [];

    foreach ($pages as $page) {
        preg_match_all("/view\('([a-z0-9._-]+)'/i", $page['code'], $matches);

        expect($matches[1])->not->toBeEmpty();

        foreach ($matches[1] as $viewName) {
            $source = '../views/'.str_replace('.', '/', $viewName).'.blade.php';

            if (! str_contains($css, "@source '{$source}';")) {
                $unreadViews[] = $page['name'].' renders '.$viewName;
            }
        }
    }

    expect($unreadViews)->toBe([]);
});

it('keeps every file the checkout stylesheet reads', function () {
    $css = file_get_contents(resource_path('css/checkout.css'));

    preg_match_all("/@source '([^']+)'/", $css, $matches);

    expect($matches[1])->not->toBeEmpty();

    $missing = array_values(array_filter(
        $matches[1],
        fn (string $source): bool => realpath(resource_path('css/'.$source)) === false,
    ));

    expect($missing)->toBe([]);
});
