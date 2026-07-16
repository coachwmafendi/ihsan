<?php

it('serves the landing page on the root domain', function () {
    $this->get('http://example.test/')->assertOk()->assertViewIs('welcome');
});

it('redirects the app panel domain root to the dashboard', function () {
    $this->get('http://app.example.test/')
        ->assertRedirect(route('app.dashboard'));
});

it('serves the landing page on hosts without a domain-scoped route', function () {
    $this->get('/')->assertOk()->assertViewIs('welcome');
});
