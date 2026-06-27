<?php

test('madrasah saidatina khadijah demo page loads', function () {
    $response = $this->get(route('demo.msk'));

    $response->assertOk()
        ->assertSee('Madrasah Saidatina Khadijah');
});

test('demo page includes required ihsan widget scripts', function () {
    $response = $this->get(route('demo.msk'));

    $response->assertOk()->assertSee('/e/widget.js');

    $html = (string) $response->getContent();

    expect($html)->toContain('data-type="button"')
        ->toContain('data-type="floating_button"')
        ->toContain('data-demo="true"');
});

test('demo page contains inline donation form section', function () {
    $this->get(route('demo.msk'))
        ->assertOk()
        ->assertSee('Buat Sumbangan')
        ->assertSee('Borang demo');
});

test('demo page shows demo modal text', function () {
    $this->get(route('demo.msk'))
        ->assertOk()
        ->assertSee('Ini Adalah Demo Sahaja')
        ->assertSee('window.__ihsanOpenDemoCheckout');
});
