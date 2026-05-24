<?php

test('simple get', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});

test('webhook get', function () {
    $response = $this->get(route('stripe.webhook'));
    $response->assertStatus(405);
});
