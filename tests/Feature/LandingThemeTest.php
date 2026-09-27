<?php

declare(strict_types=1);

/**
 * The landing picked its darks as bare hex values written into the markup, so
 * the same colour appeared under three spellings and nobody could tell which
 * one meant "page" and which meant "band". Naming them is only worth doing if
 * they stay named.
 */
it('picks the landing colours by name, not by hex', function () {
    // The first version of this guard read welcome.blade.php alone, so the
    // body colour the whole page sits on stayed a bare hex and the test still
    // passed. The layout that sets it belongs here too.
    foreach (['views/welcome.blade.php', 'views/layouts/landing.blade.php'] as $file) {
        expect(file_get_contents(resource_path($file)))
            ->not->toContain('bg-[#')
            ->not->toContain('text-[#');
    }
});

it('defines a role for each named landing colour', function () {
    $css = file_get_contents(resource_path('css/landing.css'));

    foreach (['--color-canvas', '--color-mockup', '--color-band'] as $token) {
        expect($css)->toContain($token);
    }
});

/**
 * Six cards, edited one at a time, drift apart. The tile is the part most
 * likely to be copied from an older card.
 */
it('gives every feature card the same icon tile', function () {
    $markup = file_get_contents(resource_path('views/welcome.blade.php'));

    preg_match('/<section id="features".*?<\/section>/s', $markup, $section);

    expect($section)->not->toBeEmpty();

    preg_match_all('/class="(w-\d+ h-\d+[^"]*rounded-[a-z0-9]+[^"]*)"/', $section[0], $tiles);

    expect($tiles[1])->toHaveCount(6)
        ->and(array_unique($tiles[1]))->toHaveCount(1);
});

/**
 * An element styled for one mode only is invisible in the other - light text on
 * a light background - and nothing else catches it, because the page still
 * renders and every test still passes.
 */
it('gives every dark colour on the landing a light counterpart', function () {
    $markup = file_get_contents(resource_path('views/welcome.blade.php'));

    preg_match_all('/class="([^"]*dark:(?:bg|text)-[^"]*)"/', $markup, $matches);

    expect($matches[1])->not->toBeEmpty();

    $unpaired = [];

    foreach ($matches[1] as $classes) {
        foreach (['bg', 'text'] as $property) {
            $hasDark = preg_match('/(?<![\w-])dark:'.$property.'-/', $classes) === 1;
            $hasLight = preg_match('/(?<![\w:-])'.$property.'-/', $classes) === 1;

            if ($hasDark && ! $hasLight) {
                $unpaired[] = $property.': '.$classes;
            }
        }
    }

    expect($unpaired)->toBe([]);
});
