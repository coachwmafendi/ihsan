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

it('is chip active only when onboarded and enabled', function () {
    $disabled = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
        'chip_enabled' => false,
    ]);

    $enabled = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
        'chip_enabled' => true,
    ]);

    $notOnboarded = Organization::factory()->create([
        'chip_brand_id' => null,
        'chip_api_key' => null,
        'chip_enabled' => true,
    ]);

    expect($disabled->chip_active)->toBeFalse()
        ->and($enabled->chip_active)->toBeTrue()
        ->and($notOnboarded->chip_active)->toBeFalse();
});

it('stores chip purchase fields on donation', function () {
    $donation = Donation::factory()->create([
        'chip_purchase_id' => 'PURCHASE123',
        'chip_checkout_url' => 'https://gate.chip-in.asia/pay/PURCHASE123',
    ]);

    expect($donation->chip_purchase_id)->toBe('PURCHASE123');
});
