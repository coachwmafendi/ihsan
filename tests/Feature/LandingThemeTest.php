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

/**
 * A hover state is as mode-dependent as a resting one. The secondary button
 * kept `hover:bg-slate-700` after the rest of the page was paired, so hovering
 * it in light mode turned a pale button dark under text that stayed dark.
 * Accent colours are exempt: teal means the same thing in both modes.
 */
it('gives every neutral hover colour on the landing a light counterpart', function () {
    $markup = file_get_contents(resource_path('views/welcome.blade.php'));

    preg_match_all('/class="([^"]*)"/', $markup, $matches);

    $unpaired = [];

    foreach ($matches[1] as $classes) {
        preg_match_all('/(?<![\w:-])hover:(bg|text|border)-(slate|white|black|gray)[\w\/.-]*/', $classes, $hovers, PREG_SET_ORDER);

        foreach ($hovers as [$hover, $property, $palette]) {
            if (preg_match('/(?<![\w-])dark:hover:'.$property.'-/', $classes) !== 1) {
                $unpaired[] = $hover.'  in:  '.trim($classes);
            }
        }
    }

    expect($unpaired)->toBe([]);
});
