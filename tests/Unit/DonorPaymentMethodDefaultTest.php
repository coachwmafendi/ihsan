<?php

use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

it('builds a card with the factory', function () {
    $card = DonorPaymentMethod::factory()->for(Donor::factory())->create([
        'brand' => 'Visa',
        'last4' => '4242',
    ]);

    expect($card->brand)->toBe('Visa')
        ->and($card->last4)->toBe('4242')
        ->and($card->stripe_payment_method_id)->toStartWith('pm_')
        ->and($card->is_default)->toBeFalse();
});
