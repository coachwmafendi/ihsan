<?php

test('dashboard route no longer exists', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->getName() === 'dashboard');

    expect($route)->toBeNull();
});
