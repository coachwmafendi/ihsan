<?php

declare(strict_types=1);

/**
 * A link to the landing pasted into WhatsApp or Facebook showed a bare URL:
 * no description, no image. The crawlers read these tags and nothing else.
 */
it('gives the home page a description and a link preview', function () {
    $image = asset('images/og-image.png');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Accept one-time and recurring donations online', false)
        ->assertSee('<meta property="og:title" content="Ihsan — Donation &amp; Fundraising Platform for Malaysian NGOs">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:url" content="'.route('home').'">', false)
        ->assertSee('<meta property="og:image" content="'.$image.'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta name="twitter:image" content="'.$image.'">', false);
});

it('ships the preview image at the size the tags declare', function () {
    [$width, $height] = getimagesize(public_path('images/og-image.png'));

    expect([$width, $height])->toBe([1200, 630]);
});

it('describes the home page in malay when the visitor reads malay', function () {
    $this->withSession(['locale' => 'ms'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Terima derma satu kali dan berulang', false)
        ->assertSee('<meta property="og:locale" content="ms_MY">', false);
});

it('lets the case study describe itself', function () {
    $this->get(route('case-studies.madrasah-darul-falah'))
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Madrasah Darul Falah × Ihsan — Case Study">', false)
        ->assertSee('<meta property="og:description" content="How a religious primary school in Kuala Nerus', false);
});
