<?php

declare(strict_types=1);

use App\Support\PaymentMethodLabel;

/**
 * The stored values are Stripe's own keys. Capitalising the first letter of one
 * put "Apple_pay" on the dashboard beside "Card" - and the CSV export was
 * making the same mistake separately.
 */
it('writes a wallet the way its owner spells it', function (string $stored, string $expected) {
    expect(PaymentMethodLabel::for($stored))->toBe($expected);
})->with([
    'apple' => ['apple_pay', 'Apple Pay'],
    'google' => ['google_pay', 'Google Pay'],
    'card' => ['card', 'Card'],
    'fpx' => ['fpx', 'FPX'],
    'grabpay' => ['grabpay', 'GrabPay'],
    'paypal' => ['paypal', 'PayPal'],
]);

it('reads an unknown method as words rather than a database key', function () {
    expect(PaymentMethodLabel::for('some_new_wallet'))->toBe('Some New Wallet');
});

it('falls back when nothing was recorded', function () {
    expect(PaymentMethodLabel::for(null))->toBe('Other')
        ->and(PaymentMethodLabel::for(''))->toBe('Other')
        // The CSV leaves the cell empty rather than writing "Other" into it.
        ->and(PaymentMethodLabel::for(null, ''))->toBe('');
});
