<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('home page uses localized organization registration text', function () {
    app()->setLocale('ms');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Daftar Organisasi')
        ->assertDontSee('Register Organization');
});
