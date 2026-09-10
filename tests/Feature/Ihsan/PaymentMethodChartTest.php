<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Filament\Widgets\PaymentMethodChart;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The chart counted card brands and called them payment methods. A wallet
 * payment carries a card network underneath it, so every Apple Pay and Google
 * Pay donation was being counted inside the Visa and Mastercard slices - the
 * one thing the chart was named for was the one thing it could not show.
 */
beforeEach(function () {
    $this->campaign = Campaign::factory()->for(Organization::factory())->create();
});

function succeededDonationPaidBy(string $type, string $brand): Donation
{
    return Donation::factory()->for(test()->campaign)->create([
        'status' => DonationStatus::Succeeded,
        'payment_method_type' => $type,
        'payment_method_brand' => $brand,
    ]);
}

function chartSlices(): array
{
    $data = (new ReflectionClass(PaymentMethodChart::class))
        ->getMethod('getData');
    $data->setAccessible(true);

    $result = $data->invoke(new PaymentMethodChart);

    return array_combine($result['labels'], $result['datasets'][0]['data']);
}

it('counts a wallet payment as a wallet, not as the card behind it', function () {
    succeededDonationPaidBy('card', 'visa');
    succeededDonationPaidBy('card', 'mastercard');
    succeededDonationPaidBy('apple_pay', 'mastercard');
    succeededDonationPaidBy('google_pay', 'visa');

    expect(chartSlices())->toBe([
        'Card' => 2,
        'Apple Pay' => 1,
        'Google Pay' => 1,
    ]);
});

it('spells the wallets the way the rest of the panel does', function () {
    // Capitalising the stored key produced "Apple_pay" elsewhere once already.
    succeededDonationPaidBy('apple_pay', 'visa');

    expect(array_keys(chartSlices()))->toBe(['Apple Pay']);
});

it('leaves attempts that never succeeded out of it', function () {
    succeededDonationPaidBy('card', 'visa');

    Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Failed,
        'payment_method_type' => 'apple_pay',
        'payment_method_brand' => 'visa',
    ]);

    expect(chartSlices())->toBe(['Card' => 1]);
});

it('gives a method it has never seen a slice of its own', function () {
    // FPX arrives through CHIP and no donation has carried it yet; nothing
    // here should need changing when one does.
    succeededDonationPaidBy('fpx', '');

    expect(chartSlices())->toBe(['FPX' => 1]);
});
