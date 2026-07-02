<?php

use App\Models\Organization;

it('is stripe active only when onboarded and enabled', function () {
    $disabled = Organization::factory()->create([
        'stripe_account_id' => 'acct_disabled',
        'stripe_onboarded' => true,
        'stripe_enabled' => false,
    ]);

    $enabled = Organization::factory()->create([
        'stripe_account_id' => 'acct_enabled',
        'stripe_onboarded' => true,
        'stripe_enabled' => true,
    ]);

    $notOnboarded = Organization::factory()->create([
        'stripe_account_id' => null,
        'stripe_onboarded' => false,
        'stripe_enabled' => true,
    ]);

    expect($disabled->stripe_active)->toBeFalse()
        ->and($enabled->stripe_active)->toBeTrue()
        ->and($notOnboarded->stripe_active)->toBeFalse();
});
