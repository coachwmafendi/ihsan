<?php

declare(strict_types=1);

/**
 * The landing picked its darks as bare hex values written into the markup, so
 * the same colour appeared under three spellings and nobody could tell which
 * one meant "page" and which meant "band". Naming them is only worth doing if
 * they stay named.
 */
it('picks the landing colours by name, not by hex', function () {
    $markup = file_get_contents(resource_path('views/welcome.blade.php'));

    expect($markup)->not->toContain('bg-[#');
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
