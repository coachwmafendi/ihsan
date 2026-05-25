# Multi-Currency Support (MYR / USD / SGD)

Date: 2026-05-25

## Overview

Allow NGO organizations to accept donations in MYR, USD, and SGD via Stripe. Admin enables currencies in org settings, donor sees currency selector on donation form (default detected by location), and all accounting/reporting is normalized to MYR via Stripe's exchange rate.

## Architecture

### Storage

**Organizations** — `settings` JSON column (no migration):
```json
{
  "accepted_currencies": ["myr", "usd", "sgd"]
}
```

**Campaigns** — `suggested_amounts` JSON column restructured:
```json
{
  "myr": {
    "one_time": [{"amount": "30", "label": ""}, {"amount": "50", "label": ""}],
    "monthly": [{"amount": "5", "label": ""}, {"amount": "10", "label": ""}],
    "default_monthly": "25"
  },
  "usd": {
    "one_time": [{"amount": "10", "label": ""}, {"amount": "20", "label": ""}],
    "monthly": [{"amount": "2", "label": ""}, {"amount": "5", "label": ""}],
    "default_monthly": "10"
  },
  "sgd": {
    "one_time": [{"amount": "10", "label": ""}, {"amount": "20", "label": ""}],
    "monthly": [{"amount": "2", "label": ""}, {"amount": "5", "label": ""}],
    "default_monthly": "10"
  },
  "impact_enabled": false
}
```

**Donations** — new columns via migration:
- `base_currency` VARCHAR(5) nullable — always `"myr"` for normalization
- `base_amount` DECIMAL(12,2) nullable — `gross_amount * exchange_rate`

Existing rows backfilled: `base_currency = 'myr'`, `base_amount = gross_amount`.

### Settings UI (Pembayaran page)

Section: **"Mata Wang Diterima"**
- 3 toggle/checkboxes: Ringgit Malaysia (RM), US Dollar ($), Singapore Dollar (S$)
- Default: MYR only (checked, disabled from unchecking)
- Auto-saves to `organizations.settings.accepted_currencies`
- Stripe Connect status unchanged

### Campaign Form — Suggested Amounts

`SuggestedAmounts` component updated:
- Tabs: frequency (`Sekali sahaja` / `Bulanan`) × currency tabs (MYR / USD / SGD)
- Each currency-frequency pair shows editable amount cards
- `default_monthly` per currency

### Donation Form — Currency Selector

1. **Detection**: JS reads `Intl.DateTimeFormat().resolvedOptions().locale` → country code:
   - `my` → MYR
   - `us` → USD  
   - `sg` → SGD
   - fallback: MYR
   - Only currencies enabled in org settings are shown

2. **UI**: Dropdown/button group showing enabled currencies. Suggested amounts refresh on change.

### Payment Flow

```
Donor selects currency → amount in that currency
  ↓
Donation created: currency=usd, gross_amount=10.00
  ↓
CreatePaymentIntent: amount=1000 (cents), currency=usd
  → Stripe charges $10.00 USD
  ↓
Payment succeeds → SyncDonationStripeDetails:
  → charge.balance_transaction.exchange_rate = 4.45 (example)
  → base_amount = 10.00 × 4.45 = 44.50
  → base_currency = "myr"
  ↓
Donation saved: currency=usd, gross_amount=10.00, base_currency=myr, base_amount=44.50
```

### Processing Fees

- `stripe_fee`: From Stripe `balance_transaction.fee` (already in balance currency / MYR)
- `processing_fee`: 2.5% of `base_amount` (MYR equivalent) — consistent accounting

### Recurring Subscriptions

- Stripe supports multi-currency subscriptions natively
- `CreateRecurringSubscription` passes `$donation->currency` — no code change needed
- Recurring invoices created in the chosen currency, Stripe handles settlement conversion

### Reporting

- All monetary totals (Insights, Revenue page, admin panels) use `base_amount` for aggregation
- Donation list displays: `RM44.50 ($10.00 USD)`
- Revenue page: all processing fees, stripe fees already in MYR

## Files Changed

| File | Change |
|------|--------|
| `app/Filament/App/Pages/Pembayaran.php` | Add currency settings section |
| `resources/views/filament/app/pages/pembayaran.blade.php` | Currency checkboxes UI |
| `app/Filament/Forms/Components/SuggestedAmounts.php` | Restructure for per-currency |
| `resources/views/filament/forms/components/suggested-amounts.blade.php` | Currency tabs |
| `app/Livewire/DonationForm.php` | Currency selector, detection, dynamic amounts |
| `resources/views/livewire/donation-form.blade.php` | Currency UI |
| `app/Actions/Stripe/SyncDonationStripeDetails.php` | Extract exchange_rate, save base_amount/base_currency |
| `app/Actions/Stripe/CreatePaymentIntent.php` | Verify currency is supported (guard) |
| `app/Http/Controllers/StripePaymentIntentController.php` | Accept and validate currency |
| `app/Models/Donation.php` | Add `base_currency`, `base_amount` to fillable/casts |
| `database/migrations/2026_05_25_*_add_base_currency_to_donations.php` | Migration |
| `app/Models/Campaign.php` | Update `suggestedAmounts()` helper |
| `app/Filament/App/Resources/Campaigns/Schemas/CampaignForm.php` | Wire up per-currency amounts |

## Migration Plan

1. Create migration: add `base_currency`, `base_amount` to `donations`
2. Backfill: `UPDATE donations SET base_currency = 'myr', base_amount = gross_amount WHERE base_currency IS NULL`
3. Update campaign `suggested_amounts` data: wrap existing into `myr` key, add default empty for `usd`/`sgd`
