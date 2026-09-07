<?php

declare(strict_types=1);

use App\Support\Currency;

/**
 * A campaign's minimum is set in ringgit, and Stripe's own floor applies to the
 * currency an account settles in - ringgit, here. Both have to be restated in
 * whatever the donor is giving in, and the live rate is not known until Stripe
 * settles the charge, so a floor rate stands in for it.
 */
it('holds a foreign charge to what our own account settles in', function (string $currency, float $expected) {
    // Stripe lists SGD 0.50, but that converts to about RM1.60 - under the RM2
    // a Malaysian account must clear. USD 0.50 already clears it.
    expect(Currency::chargeMinimum($currency))->toBe($expected);
})->with([
    'ringgit' => ['myr', 2.00],
    'dollars' => ['usd', 0.50],
    'Singapore dollars' => ['sgd', 0.70],
]);

it('states a ringgit minimum in the currency the donor is giving in', function (string $currency, float $expected) {
    // A RM10 minimum applied straight to dollars asked four times as much as
    // anyone configured, and refused a USD 5 gift.
    expect(Currency::minimumDonation(10.0, $currency))->toBe($expected);
})->with([
    'ringgit' => ['myr', 10.00],
    'dollars' => ['usd', 2.50],
    'Singapore dollars' => ['sgd', 3.35],
]);

it('never falls below what Stripe would accept', function () {
    // A campaign that asks for almost nothing still cannot charge below Stripe's
    // floor, so the minimum shown has to be the higher of the two.
    expect(Currency::minimumDonation(1.0, 'sgd'))->toBe(0.70)
        ->and(Currency::minimumDonation(1.0, 'myr'))->toBe(2.00);
});

it('rounds a converted figure up, never down', function () {
    // Rounding down would put the stated minimum under the real one and the
    // charge would be refused at the last moment.
    expect(Currency::fromRinggit(1.00, 'sgd'))->toBe(0.35)
        ->and(Currency::fromRinggit(1.00, 'usd'))->toBe(0.25);
});
