# Supporter Payment Methods — one row per card, one default

**Date:** 2026-09-16
**Status:** Approved, ready for planning

## Problem

The Payment Methods card on the supporter page (e.g. `https://app.getihsan.my/supporters/DRV8ZTTA`)
lists the same card over and over, and several rows carry a `Default` badge at once.

Two separate causes:

1. **Duplicate rows are expected data, badly presented.** `donor_payment_methods.stripe_payment_method_id`
   is unique and Stripe mints a new `pm_` for every checkout, so one physical card produces one row per
   donation. Six Mastercard rows means six donations on that card, not six cards.
2. **Multiple defaults is a real bug.** Two writers set `is_default => true` without clearing the
   donor's other rows, so defaults accumulate:
   - `app/Actions/Stripe/SyncDonationStripeDetails.php` — runs on every succeeded donation
   - `app/Http/Controllers/DonorSubscriptionController.php` — donor-portal card update

   Three writers already clear siblings first: `UpdateRecurringPaymentMethod`,
   `UpdateAppControlledPaymentMethod`, `CreateAppControlledRecurringPlan`.
   `LoadDonorSavedCards` is also correct — it rewrites every card of the customer in one pass, so
   `is_default => $index === 0` leaves exactly one default.

A stale extra default is not only cosmetic: `app/Livewire/App/VirtualTerminal.php` orders saved cards
by `is_default` descending, so with several defaults it can preselect the wrong card for a staff-taken
donation.

## Out of scope

Deduplicating the stored rows (a card fingerprint column, one row per physical card, many `pm_` ids
hanging off it) is deliberately excluded. It changes money-path write code for a win the grouped view
already delivers. Each `pm_` stays its own row; only the presentation groups them.

## Design

### 1. `DonorPaymentMethod::markAsSoleDefault()`

One method replaces the copy-pasted "set true, clear the others" idiom.

```php
public function markAsSoleDefault(): void
```

Inside a transaction: set `is_default = true` on itself, and `is_default = false` on every other row
with the same `donor_id`. All five call sites use it, which removes the drift that caused the bug.

### 2. `NormalizeDonorDefaultPaymentMethods` action

Repairs rows already written. For each donor holding more than one default, keep the most recently
created default and clear the rest. Donors with zero or one default are untouched — this action never
invents a default where none exists.

A migration calls the action so production self-heals on deploy. The migration body stays a single
call; the action is unit-tested directly.

### 3. Grouped list

`SupporterShow::paymentMethods()` groups rows by `brand|last4|exp_month|exp_year` and returns one
entry per physical card. Each entry carries: the newest row as representative, the number of rows in
the group, whether any row is default, and the newest `created_at`.

Existing ordering is preserved — soonest expiry first, missing expiry last — applied to the groups.

Row layout:

```
[brand logo]  Mastercard •••• 0697   Default   ×6
              Expires 05/2029 · Last saved 15 Sep 2026
```

- The `×6` chip only appears when the group holds more than one row. Its tooltip reads: "Stripe issues
  a new token each time this card is charged. These 6 records are the same card." (count interpolated)
- `Default` shows when any row in the group is default
- `Expired` / `Expiring soon` badges keep their current logic and wording; every row in a group shares
  one expiry, so the badge is computed from the representative
- `Last saved <date>` uses the group's newest `created_at`, rendered with `myrTime`-style Malaysian
  formatting consistent with the rest of the page

The generic grey `heroicon-o-credit-card` is replaced with the real brand logo. `DonorPaymentMethod`
gains a `cardIconComponent` accessor mapping `brand` to `icons.visa`, `icons.mastercard`, `icons.amex`,
`icons.discover`, `icons.diners`, `icons.jcb`, `icons.maestro`, `icons.unionpay`, falling back to
`icons.credit-card` — mirroring the existing accessor on `Donation`. Rendered `h-6 w-auto shrink-0`,
the height-driven sizing already used elsewhere.

### Empty state

Unchanged.

## Testing

- **Unit** — `markAsSoleDefault()` clears siblings and spares other donors' rows.
- **Unit** — `NormalizeDonorDefaultPaymentMethods` collapses several defaults to the newest, leaves a
  single default alone, and leaves a donor with no default alone.
- **Feature (`SupporterShowPageTest`)** — six rows of one card render one row bearing `×6`; a single
  row renders no chip; two genuinely different cards render two rows; `Default` appears once when
  several rows in a group were flagged; the brand logo renders instead of the generic icon.
- **Feature** — a succeeded donation synced through `SyncDonationStripeDetails` leaves exactly one
  default for that donor.

## Risks

Grouping is presentation-only, so the stored rows and every money path are untouched. The one
behavioural change outside the view is that a donor ends with exactly one default row, which is what
the three already-correct writers assume.
