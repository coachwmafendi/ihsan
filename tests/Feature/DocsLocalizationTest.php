<?php

test('documentation renders in english by default', function () {
    $response = $this->get('/docs');

    $response->assertStatus(200);
    $response->assertSee('Ihsan Documentation', false);
    $response->assertSee('Getting started', false);
});

test('documentation renders in bahasa malaysia after switching locale', function () {
    $this->get('/language/ms')->assertRedirect();

    $response = $this->get('/docs');

    $response->assertStatus(200);
    $response->assertSee('Dokumentasi Ihsan', false);
    $response->assertSee('Permulaan', false);
    $response->assertSee('Selamat datang ke dokumentasi Ihsan', false);
});

test('bahasa malaysia docs article renders from translated markdown', function () {
    $this->get('/language/ms');

    $response = $this->get('/docs/getting-started');

    $response->assertStatus(200);
    $response->assertSee('Permulaan', false);
    $response->assertSee('Pemasangan', false);
});

test('docs falls back to english when localized markdown is missing', function () {
    app()->setLocale('fr');

    $response = $this->get('/docs');

    $response->assertStatus(200);
    $response->assertSee('Ihsan Documentation', false);
    $response->assertDontSee('Dokumentasi Ihsan', false);
});
