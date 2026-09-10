<?php

declare(strict_types=1);

use App\Support\WalletCardLabel;

/**
 * The wallet marks in the lists carried no hover text while the plain card
 * icon beside them named its brand - and the wallet mark hides more, not less.
 * The card underneath is the thing that was charged, the thing that gets
 * declined, and the thing a supporter names when they ring about a payment.
 */
it('names the card a wallet payment settled on', function () {
    expect(WalletCardLabel::for('apple_pay', 'mastercard', '0697'))
        ->toBe('Apple Pay · Mastercard •••• 0697');
});

it('leaves out the digits when they were never recorded', function () {
    expect(WalletCardLabel::for('google_pay', 'visa', null))
        ->toBe('Google Pay · Visa');
});

it('falls back to the wallet alone when nothing is known of the card', function () {
    expect(WalletCardLabel::for('apple_pay', null, null))->toBe('Apple Pay')
        ->and(WalletCardLabel::for('apple_pay', '', ''))->toBe('Apple Pay');
});

it('spells the wallet the way the rest of the panel does', function () {
    // Capitalising the stored key produced "Apple_pay" on the dashboard once.
    expect(WalletCardLabel::for('apple_pay', null, null))->not->toContain('_');
});
