# Donor Covers Processing Fee — Design Spec

**Date:** 2026-05-26
**Branch:** feature/donor-covers-fee (to be created)
**Reference:** Donorbox implementation pattern

---

## Overview

Allow donors to optionally cover Stripe's processing fee so the organisation receives the full intended donation amount. Pre-checked by default (Donorbox style). Configurable per campaign.

---

## 1. Data Model

### Migration: `donations` table
Add one column:

```php
$table->decimal('donor_fee_covered', 12, 2)->default(0)->after('stripe_fee');
```

`donor_fee_covered` stores the extra amount the donor agreed to pay. Zero means they did not cover the fee.

### Donation model
- Add `donor_fee_covered` to `$fillable`
- Add `'donor_fee_covered' => 'decimal:2'` to `$casts`

### Element config (JSON, per-campaign)
New key: `allow_cover_fee` — boolean, default `true`.

Stored in the existing `config` JSON column on `elements`. Follows the same pattern as `allow_monthly`.

---

## 2. UI/UX

Checkbox appears in **Step 1 (Choose Amount)**, between the amount input and the Continue button. Only renders when `allow_cover_fee` config is `true`.

```
┌─────────────────────────────────────────────────┐
│ RM  200                                   MYR   │
└─────────────────────────────────────────────────┘

☑ I'll cover the processing fee (+RM 6.50)
  Help ensure 100% of your donation reaches us.

[ Continue → ]
```

- **Pre-checked by default**
- `+RM X.XX` updates reactively as donor changes amount (Livewire)
- Subtext explains the purpose
- Continue button label unchanged — no confusion about total

---

## 3. Fee Calculation

Stripe Malaysia standard card rate: **3% + RM 0.50**

```php
private const STRIPE_PERCENT = 0.03;
private const STRIPE_FIXED   = 0.50;

#[Computed]
public function estimatedFee(): float
{
    if (! $this->coverFee || ! $this->config('allow_cover_fee', true)) {
        return 0.0;
    }

    return round((float) $this->amount * self::STRIPE_PERCENT + self::STRIPE_FIXED, 2);
}
```

**Charge to Stripe** = `amount + estimatedFee` when `coverFee = true`

Actual Stripe fee may differ slightly (e.g. international cards, Amex). Difference absorbed in `net_amount` — same behaviour as Donorbox.

### Donation record fields

| Field | Value |
|---|---|
| `gross_amount` | Donor's intended amount (e.g. 200.00) |
| `donor_fee_covered` | Estimated fee donor agreed to cover (e.g. 6.50) |
| `stripe_fee` | Actual Stripe fee (synced post-charge) |
| `net_amount` | Amount org receives after all fees |

---

## 4. Livewire Component Changes (`DonationForm.php`)

- Add `public bool $coverFee = true` property
- Add `#[Computed] estimatedFee()` computed property
- `preparePaymentIntent()`: use `$this->amount + $this->estimatedFee` as Stripe charge amount
- `storeDonation()`: save `donor_fee_covered` = `$this->coverFee ? $this->estimatedFee : 0`
- Validation: `coverFee` is boolean, no special rules needed

---

## 5. Campaign Config (ElementForm)

Add toggle in **ElementForm.php** Settings tab alongside `allow_monthly`:

```php
Toggle::make('allow_cover_fee')
    ->label('Allow donors to cover processing fee')
    ->default(true),
```

Add `allow_cover_fee` to the config keys allowlist array.

---

## 6. Testing

### Feature tests
- `donor_can_opt_in_to_cover_fee` — checkbox checked → PaymentIntent amount = gross + fee
- `donor_can_opt_out_of_cover_fee` — checkbox unchecked → PaymentIntent amount = gross only
- `cover_fee_hidden_when_disabled_in_campaign_config` — `allow_cover_fee=false` → checkbox absent
- `donor_fee_covered_stored_correctly_on_donation` — column saved with correct value

### Unit tests
- `estimated_fee_is_correct_for_given_amount`:
  - RM 100.00 → RM 3.50
  - RM 200.00 → RM 6.50
  - RM 1.00 → RM 0.53

### ElementForm test
- `allow_cover_fee_toggle_saves_to_campaign_config`

---

## 7. Out of Scope

- Currency-specific Stripe rates (USD/SGD) — use same 3% + 0.50 flat estimate for now
- Stripe + platform fee combined coverage — Stripe only
- Admin global override — per-campaign only
