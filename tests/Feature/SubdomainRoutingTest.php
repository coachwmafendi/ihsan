<?php

declare(strict_types=1);

it('registers fortify login on the app panel domain', function () {
    $route = app('router')->getRoutes()->getByName('login');

    expect($route->getDomain())->toBe('app.example.test');
    expect(app('router')->getRoutes()->getByName('logout')->getDomain())->toBe('app.example.test');
});

it('redirects to the dashboard after login', function () {
    expect(config('fortify.home'))->toBe('/dashboard');
});
