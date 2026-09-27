<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders one shared footer on the landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('footer.copyright'))
        ->assertSee('data-landing-footer', false);
});

it('renders the same footer on the case study page', function () {
    $this->get(route('case-studies.madrasah-darul-falah'))
        ->assertOk()
        ->assertSee('data-landing-footer', false);
});

it('serves the privacy policy', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('Last updated')
        ->assertSee('data-landing-footer', false);
});

it('serves the terms of service', function () {
    $this->get('/terms')
        ->assertOk()
        ->assertSee('Terms of Service')
        ->assertSee('Last updated')
        ->assertSee('data-landing-footer', false);
});

it('names both legal routes', function () {
    expect(route('legal.privacy'))->toEndWith('/privacy')
        ->and(route('legal.terms'))->toEndWith('/terms');
});

it('links to both documents from the landing footer', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('legal.privacy').'"', false)
        ->assertSee('href="'.route('legal.terms').'"', false)
        ->assertSee('Privacy Policy')
        ->assertSee('Terms of Service');
});

it('translates the footer link labels', function () {
    app()->setLocale('ms');

    $this->get('/')
        ->assertOk()
        ->assertSee('Dasar Privasi')
        ->assertSee('Terma Perkhidmatan');
});

it('covers each part of the privacy policy', function () {
    $response = $this->get('/privacy')->assertOk();

    foreach ([
        'What we collect',
        'Card details',
        'Why we collect it',
        'Who else sees it',
        'How long we keep it',
        'Your choices',
        'Changes to this policy',
    ] as $heading) {
        $response->assertSee($heading);
    }
});

it('says plainly that Ihsan never holds card numbers', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee('never see or store your card number');
});
