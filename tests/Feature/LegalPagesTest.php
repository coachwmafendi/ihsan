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
