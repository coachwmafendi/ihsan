<?php

declare(strict_types=1);

it('renders the branded 404 page for unknown routes', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Error 404')
        ->assertSee("This page doesn't exist", false)
        ->assertSee('Go to homepage');
});

it('renders the branded 404 page on the app panel domain', function () {
    $this->get('https://app.example.test/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Error 404')
        ->assertSee("This page doesn't exist", false)
        ->assertSee('Go to homepage');
});
