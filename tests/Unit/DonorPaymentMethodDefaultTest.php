<?php

use App\Actions\Stripe\NormalizeDonorDefaultPaymentMethods;
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

it('clears the other cards of the same donor when one is made default', function () {
    $donor = Donor::factory()->create();
    $old = DonorPaymentMethod::factory()->for($donor)->default()->create();
    $new = DonorPaymentMethod::factory()->for($donor)->create();

    $new->markAsSoleDefault();

    expect($new->refresh()->is_default)->toBeTrue()
        ->and($old->refresh()->is_default)->toBeFalse();
});

it('leaves the cards of other donors untouched', function () {
    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();
    $otherCard = DonorPaymentMethod::factory()->for($otherDonor)->default()->create();

    DonorPaymentMethod::factory()->for($donor)->create()->markAsSoleDefault();

    expect($otherCard->refresh()->is_default)->toBeTrue();
});

it('keeps only the newest default when a donor has several', function () {
    $donor = Donor::factory()->create();
    $oldest = DonorPaymentMethod::factory()->for($donor)->default()->create(['created_at' => now()->subDays(3)]);
    $middle = DonorPaymentMethod::factory()->for($donor)->default()->create(['created_at' => now()->subDays(2)]);
    $newest = DonorPaymentMethod::factory()->for($donor)->default()->create(['created_at' => now()->subDay()]);

    $cleared = app(NormalizeDonorDefaultPaymentMethods::class)->run();

    expect($cleared)->toBe(2)
        ->and($newest->refresh()->is_default)->toBeTrue()
        ->and($middle->refresh()->is_default)->toBeFalse()
        ->and($oldest->refresh()->is_default)->toBeFalse();
});

it('leaves a donor with a single default alone', function () {
    $donor = Donor::factory()->create();
    $card = DonorPaymentMethod::factory()->for($donor)->default()->create();
    DonorPaymentMethod::factory()->for($donor)->create();

    app(NormalizeDonorDefaultPaymentMethods::class)->run();

    expect($card->refresh()->is_default)->toBeTrue();
});

it('never invents a default for a donor that has none', function () {
    $donor = Donor::factory()->create();
    DonorPaymentMethod::factory()->for($donor)->count(2)->create();

    app(NormalizeDonorDefaultPaymentMethods::class)->run();

    expect(DonorPaymentMethod::where('donor_id', $donor->id)->where('is_default', true)->count())->toBe(0);
});
