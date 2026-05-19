<?php

it('returns validation errors for invalid input', function () {
    $response = $this->postJson(route('stripe.payment-intent'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['campaign_id', 'donor_name', 'donor_email', 'amount']);
});

it('creates a pending donation and requires a campaign', function () {
    $response = $this->postJson(route('stripe.payment-intent'), [
        'campaign_id' => 99999,
        'donor_name' => 'Test Donor',
        'donor_email' => 'test@example.com',
        'amount' => 50,
        'currency' => 'myr',
        'type' => 'one_time',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['campaign_id']);
});
