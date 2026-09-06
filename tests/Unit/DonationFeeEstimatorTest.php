<?php

declare(strict_types=1);

use App\Services\DonationFeeEstimator;
use Tests\TestCase;

pest()->extend(TestCase::class);

/**
 * The fee cover exists so the organization receives the amount the donor
 * intended. Adding the fee percentages to the donation is not enough: the
 * processor charges its percentage on the grossed-up total, so the cover has
 * to be solved for, not added up.
 */
it('grosses up the cover so the organization nets the donation', function (float $donation) {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $cover = DonationFeeEstimator::estimate($donation, 'myr', 'stripe', null, 'MY');
    $total = $donation + $cover;

    // Stripe Malaysia domestic cards and FPX: 3% + RM1.00.
    $processorFee = $total * 0.03 + 1.00;
    $platformFee = $donation * 0.025;

    expect($total - $processorFee - $platformFee)->toBeGreaterThanOrEqual($donation);
})->with([10.0, 25.0, 50.0, 100.0, 500.0, 1000.0]);

it('produces the documented cover amounts for a Malaysian donor paying MYR', function (float $donation, float $expectedCover) {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    expect(DonationFeeEstimator::estimate($donation, 'myr', 'stripe', null, 'MY'))->toBe($expectedCover);
})->with([
    [10.0, 1.60],
    [50.0, 3.87],
    [100.0, 6.71],
    [1000.0, 57.74],
]);

it('rounds the cover up so rounding never shortchanges the organization', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $cover = DonationFeeEstimator::estimate(33.33, 'myr', 'stripe', null, 'MY');

    expect($cover)->toBe(round($cover, 2))
        ->and($cover * 100)->toBe((float) ceil($cover * 100));
});

it('charges no cover for a zero donation', function () {
    expect(DonationFeeEstimator::estimate(0, 'myr', 'stripe'))->toBe(0.0);
});

it('uses a negotiated platform rate when one is given', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $standard = DonationFeeEstimator::estimate(100.0, 'myr', 'stripe', null, 'MY');
    $negotiated = DonationFeeEstimator::estimate(100.0, 'myr', 'stripe', 1.5, 'MY');

    expect($negotiated)->toBeLessThan($standard);
});

it('exposes the processor and platform rates for the checkout script', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $rates = DonationFeeEstimator::rates('stripe', null, 'MY');

    expect($rates['myr'])
        ->toHaveKeys(['percent', 'fixed', 'platform'])
        ->and($rates['myr']['percent'])->toBe(0.03)
        ->and($rates['myr']['fixed'])->toBe(1.00)
        ->and($rates['myr']['platform'])->toBe(0.025);
});

/**
 * A donation presented in SGD or USD on a Malaysian account is always an
 * international card settling through a currency conversion, which production
 * balance transactions confirm as 6% + RM1.00 rather than the domestic 3%.
 */
it('covers the international and conversion surcharges on foreign currencies', function (string $currency, float $fixedInCurrency) {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $donation = 100.0;
    $cover = DonationFeeEstimator::estimate($donation, $currency, 'stripe');
    $total = $donation + $cover;

    $processorFee = $total * 0.06 + $fixedInCurrency;
    $platformFee = $donation * 0.025;

    expect($total - $processorFee - $platformFee)->toBeGreaterThanOrEqual($donation);
})->with([
    // RM1.00 converted at roughly the rates production settles at.
    ['sgd', 0.32],
    ['usd', 0.25],
]);

it('quotes the foreign currency processor rate that production settles at', function () {
    $rates = DonationFeeEstimator::rates('stripe');

    expect($rates['sgd']['percent'])->toBe(0.06)
        ->and($rates['usd']['percent'])->toBe(0.06);
});

/**
 * Stripe charges 3% on a Malaysian card, +1% when the card is foreign and +2%
 * when the charge converts. Production settlements confirm every combination to
 * within 0.03%, so the cover has to know where the card is from - not just
 * which currency the donor picked.
 */
it('adds the international surcharge when the donor is abroad', function (string $currency, ?string $country, float $expectedPercent) {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $rates = DonationFeeEstimator::rates('stripe', null, $country);

    expect($rates[$currency]['percent'])->toBe($expectedPercent);
})->with([
    'MYR from Malaysia' => ['myr', 'MY', 0.03],
    'MYR from abroad' => ['myr', 'SG', 0.04],
    'SGD from Malaysia' => ['sgd', 'MY', 0.05],
    'SGD from abroad' => ['sgd', 'SG', 0.06],
    'USD from Malaysia' => ['usd', 'MY', 0.05],
    'USD from abroad' => ['usd', 'US', 0.06],
]);

it('assumes a foreign card when the country is unknown', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    // Guessing domestic would leave the organization short; guessing foreign
    // only costs a local donor whose IP could not be resolved a little extra.
    expect(DonationFeeEstimator::rates('stripe', null, null)['myr']['percent'])->toBe(0.04);
});

it('covers a foreign card paying in ringgit', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $donation = 100.0;
    $cover = DonationFeeEstimator::estimate($donation, 'myr', 'stripe', null, 'SG');
    $total = $donation + $cover;

    // Measured: MYR charges on a foreign card settle at 4% + RM1.00.
    $processorFee = $total * 0.04 + 1.00;

    expect($total - $processorFee - $donation * 0.025)->toBeGreaterThanOrEqual($donation);
});

it('does not overcharge a Malaysian donor paying in ringgit', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $local = DonationFeeEstimator::estimate(100.0, 'myr', 'stripe', null, 'MY');
    $foreign = DonationFeeEstimator::estimate(100.0, 'myr', 'stripe', null, 'SG');

    expect($local)->toBeLessThan($foreign);
});
