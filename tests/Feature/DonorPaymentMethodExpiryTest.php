<?php

declare(strict_types=1);

use App\Models\DonorPaymentMethod;

function paymentMethodExpiring(?int $month, ?int $year): DonorPaymentMethod
{
    return new DonorPaymentMethod(['exp_month' => $month, 'exp_year' => $year]);
}

beforeEach(function () {
    $this->travelTo('2026-08-19');
});

it('treats a card as valid through the whole of its expiry month', function () {
    // Expires this month: still chargeable until the month is out.
    expect(paymentMethodExpiring(8, 2026)->isExpired())->toBeFalse()
        ->and(paymentMethodExpiring(7, 2026)->isExpired())->toBeTrue();
});

it('flags cards expiring within the next two months', function () {
    expect(paymentMethodExpiring(8, 2026)->expiresSoon())->toBeTrue()
        ->and(paymentMethodExpiring(10, 2026)->expiresSoon())->toBeTrue()
        ->and(paymentMethodExpiring(11, 2026)->expiresSoon())->toBeFalse();
});

it('does not call an already expired card expiring soon', function () {
    $expired = paymentMethodExpiring(1, 2026);

    expect($expired->isExpired())->toBeTrue()
        ->and($expired->expiresSoon())->toBeFalse();
});

it('reports nothing when the expiry was never captured', function () {
    expect(paymentMethodExpiring(null, null)->expiresAt())->toBeNull()
        ->and(paymentMethodExpiring(null, null)->isExpired())->toBeFalse()
        ->and(paymentMethodExpiring(null, null)->expiresSoon())->toBeFalse()
        ->and(paymentMethodExpiring(12, null)->expiresAt())->toBeNull();
});
