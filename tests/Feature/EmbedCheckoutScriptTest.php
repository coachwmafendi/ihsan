<?php

declare(strict_types=1);

it('returns the embed script with donation success handling', function () {
    $this->get(route('embed.script'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
        ->assertSee("'ihsan:donation-success'", false)
        ->assertSee('window.location.reload()', false);
});
