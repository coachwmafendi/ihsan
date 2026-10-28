<?php

declare(strict_types=1);

use App\Models\Organization;

/**
 * NGOs send their donors the portal login link over WhatsApp; the preview
 * should say whose portal it is, not show a bare URL.
 */
it('gives the donor portal login a link preview in the organization name', function () {
    $organization = Organization::factory()->create(['name' => 'Masjid Al-Falah']);
    $image = asset('images/og-donor-portal.png');

    $this->get(route('donorportal.login', $organization))
        ->assertOk()
        ->assertSee('<meta name="description" content="Sign in to the Masjid Al-Falah donor portal', false)
        ->assertSee('<meta property="og:title" content="Masjid Al-Falah — Donor Portal">', false)
        ->assertSee('<meta property="og:site_name" content="Masjid Al-Falah">', false)
        ->assertSee('<meta property="og:image" content="'.$image.'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

it('keeps donor portal pages out of search results', function () {
    $organization = Organization::factory()->create();

    $this->get(route('donorportal.login', $organization))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('ships the donor portal preview image at the size the tags declare', function () {
    expect(array_slice(getimagesize(public_path('images/og-donor-portal.png')), 0, 2))->toBe([1200, 630]);
});
