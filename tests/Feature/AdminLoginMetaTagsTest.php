<?php

declare(strict_types=1);

/**
 * The admin login link gets shared between staff in WhatsApp; without these
 * tags the preview was a bare URL.
 */
it('gives the admin login a description and a link preview', function () {
    $image = asset('images/og-admin.png');

    $this->get(route('filament.admin.auth.login'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Sign in to the Ihsan platform admin console', false)
        ->assertSee('<meta property="og:title" content="Ihsan Admin — Platform Admin Console">', false)
        ->assertSee('<meta property="og:image" content="'.$image.'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

it('keeps the admin console out of search results', function () {
    $this->get(route('filament.admin.auth.login'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('ships the admin preview image at the size the tags declare', function () {
    expect(array_slice(getimagesize(public_path('images/og-admin.png')), 0, 2))->toBe([1200, 630]);
});
