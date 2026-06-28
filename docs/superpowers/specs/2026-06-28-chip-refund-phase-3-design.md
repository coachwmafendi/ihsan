# CHIP Refund (Phase 3) Design

## Goal
Allow admins to refund CHIP donations from the existing donation detail page, mirroring the Stripe refund flow.

## Constraints
- Full refund only (same as current Stripe behaviour — no partial refund UI yet).
- No new database table; reuse `donations.refunded_at` and `DonationStatus::Refunded`.
- No dependency changes; use Laravel HTTP client via `ChipApi`.

## Refund API

CHIP refund endpoint:
```
POST /purchases/{id}/refund/
```
Optionally pass `{ "amount": <cents> }` for a partial refund. Omit `amount` for a full refund.

Response status codes:
- `200` — Refund accepted. Body is a Payment object. The Purchase may have status `refunded` immediately or `pending_refund` if async.
- `400` — Refund error (e.g. `purchase_refund_error`).
- `404` — Purchase not found.

## Implementation

### 1. `ChipApi::refundPurchase`

Add a method that posts to `/purchases/{chip_purchase_id}/refund/` and returns the Payment response. No amount body for full refund.

### 2. `App\Actions\Chip\RefundDonation`

Mirror `App\Actions\Stripe\RefundDonation`:
- Require `chip_purchase_id`.
- Load `campaign.organization`.
- Call `ChipApi::refundPurchase`.
- Update `donation.status = Refunded` and `refunded_at = now()`.
- Decrement `campaign.collected_amount` by `base_amount ?? gross_amount`, clamped at zero.
- Dispatch `SendRefundNotification` and `SendDonorRefundNotification`.

### 3. `DonationShow::canRefund`

Change the refund eligibility check to allow CHIP donations:

```php
return $this->donation->status === DonationStatus::Succeeded
    && (filled($this->donation->stripe_charge_id) || filled($this->donation->chip_purchase_id));
```

### 4. `DonationShow::confirmRefund`

Branch to the correct gateway action:

```php
if (filled($this->donation->chip_purchase_id)) {
    app(\App\Actions\Chip\RefundDonation::class)->handle($this->donation);
} else {
    app(\App\Actions\Stripe\RefundDonation::class)->handle($this->donation);
}
```

### 5. Tests

Add to `tests/Feature/ChipRecurringDonationTest.php` or create `tests/Feature/ChipRefundDonationTest.php`:
- Mock `POST /purchases/{chip_purchase_id}/refund/` to return status `200` with a refund Payment.
- Call the CHIP refund action on a succeeded donation.
- Assert:
  - Donation status becomes `Refunded`.
  - `refunded_at` is set.
  - `campaign.collected_amount` is decremented by the donation amount.
  - `Http::recorded()` contains the refund request.

## Files likely changed
- `app/Services/ChipApi.php`
- `app/Actions/Chip/RefundDonation.php` (new)
- `app/Livewire/App/Donations/DonationShow.php`
- `tests/Feature/ChipRefundDonationTest.php` (new)
