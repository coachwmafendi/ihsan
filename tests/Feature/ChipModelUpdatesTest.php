<?php

use App\Models\Donation;
use App\Models\Organization;

it('stores chip fields on organization', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);

    expect($organization->chip_onboarded)->toBeTrue();
    expect($organization->chipPaymentMethods())->toContain('card');
});

it('stores chip purchase fields on donation', function () {
    $donation = Donation::factory()->create([
        'chip_purchase_id' => 'PURCHASE123',
        'chip_checkout_url' => 'https://gate.chip-in.asia/pay/PURCHASE123',
    ]);

    expect($donation->chip_purchase_id)->toBe('PURCHASE123');
});
