<?php

test('madrasah darul falah case study page loads', function () {
    $response = $this->get(route('case-studies.madrasah-darul-falah'));

    $response->assertOk()
        ->assertSee('Madrasah Darul Falah')
        ->assertSee('Case Study')
        ->assertSee('At a glance')
        ->assertSee('madrasahdarulfalah.org');
});

test('case study page uses a light background and shows story images', function () {
    $response = $this->get(route('case-studies.madrasah-darul-falah'));

    $response->assertOk()
        ->assertSee('bg-white text-slate-600 font-sans antialiased', false)
        ->assertSee('<figure', false)
        ->assertSee('Madrasah Darul Falah, Kg. Pengkalan Maras, Kuala Nerus, Terengganu');
});

test('case study page shows key results and story sections', function () {
    $this->get(route('case-studies.madrasah-darul-falah'))
        ->assertOk()
        ->assertSee('2.1x')
        ->assertSee('The challenge')
        ->assertSee('The solution')
        ->assertSee('The results')
        ->assertSee('RM1.7 million');
});

test('case study page renders in bahasa melayu when locale is ms', function () {
    $this->withSession(['locale' => 'ms'])
        ->get(route('case-studies.madrasah-darul-falah'))
        ->assertOk()
        ->assertSee('Kajian Kes')
        ->assertSee('Cabaran')
        ->assertSee('Penyelesaian')
        ->assertSee('Sekilas pandang');
});

test('landing page links to the case study', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('case-studies.madrasah-darul-falah'));
});
