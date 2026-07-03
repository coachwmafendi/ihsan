# Dashboard Recurring Revenue Health — Design Spec

## Goal

Add recurring-revenue visibility to `/app/dashboard`, currently absent despite the `Subscription` model holding all the data needed (status, amount, interval, next_charge_at). Three new stat cards in the existing top stats row: MRR, at-risk subscription count, expected charges in the next 30 days.

## Scope

Additive only — no changes to existing stat cards, charts, or tables on the dashboard. No new files; small additions to `app/Livewire/App/Dashboard.php` and `resources/views/livewire/app/dashboard.blade.php`.

## Metrics

1. **MRR (Monthly Recurring Revenue)** — sum of `amount` for all `Subscription` rows with `status = Active`, each normalized to a monthly equivalent based on `interval`:
   | Interval | Multiplier |
   |---|---|
   | Weekly | × 52/12 (≈ 4.333) |
   | Biweekly | × 26/12 (≈ 2.167) |
   | Monthly | × 1 |
   | Bimonthly | × 6/12 (0.5) |
   | Quarterly | × 4/12 (≈ 0.333) |
   | Semiannual | × 2/12 (≈ 0.167) |
   | Yearly | × 1/12 (≈ 0.083) |

2. **At-risk subscriptions** — count of `Subscription` rows with `status` in (`PastDue`, `Failed`). No other conditions (an `Active` subscription with a nonzero `failed_installment_count` does not count).

3. **Expected next 30 days** — sum of `amount` for `Subscription` rows with `status = Active` and `next_charge_at` between now and now+30 days (inclusive). Raw scheduled-charge amounts, not monthly-normalized (each subscription contributes at most once, for its next single charge in that window).

## Period-filter behavior

All three metrics are live snapshots computed from current subscription state — they ignore the dashboard's existing period filter (today/7d/30d/custom) entirely. They always reflect "right now," unlike the other existing stat cards which scope to the selected date range.

## Currency handling

`Subscription` has no `base_amount`/exchange-rate snapshot (unlike `Donation`, which resolves this at charge time). Assume one currency per organization — sum raw `amount` values with no conversion.

If any `Active` subscription counted in MRR or the 30-day metric has `currency != 'myr'`, mark that card with the existing "≈" approximation badge (the same visual pattern the dashboard already uses via `has_approximation` on the trend chart and campaign breakdown), so a mixed-currency org sees the number is approximate rather than silently wrong. Detection: `has_report_approximations`-style boolean — true if any contributing subscription has `currency != 'myr'`.

## Implementation shape

- New `#[Computed] public function recurringHealth(): array` on `app/Livewire/App/Dashboard.php`, scoped to the current organization (same `$this->organization` pattern as every other computed method in this file), returning:
  ```php
  [
      'mrr' => float,
      'mrr_has_approximation' => bool,
      'at_risk_count' => int,
      'expected_30_days' => float,
      'expected_30_days_has_approximation' => bool,
  ]
  ```
- Three new stat-card blocks in `resources/views/livewire/app/dashboard.blade.php`, placed in the existing top stats row, using the same markup/CSS classes as the current stat cards (Total Donations, Donors, etc.) for visual consistency.
- No new routes, no new Livewire component, no new model/migration — pure read-only aggregation over existing `Subscription` data.

## Testing

Feature test on the `Dashboard` Livewire component (new test file or appended to an existing Dashboard test if one exists) asserting `recurringHealth()` output against factory-seeded subscriptions covering:
- One `Active` monthly subscription and one `Active` weekly subscription → correct MRR sum using the multiplier table.
- One `PastDue` and one `Failed` subscription → `at_risk_count = 2`; an `Active` subscription with `failed_installment_count > 0` does NOT count toward it.
- One `Active` subscription with `next_charge_at` in 10 days and one with `next_charge_at` in 45 days → only the 10-day one contributes to `expected_30_days`.
- One `Active` subscription with `currency != 'myr'` → `mrr_has_approximation` and/or `expected_30_days_has_approximation` is `true` when it contributes to that metric.
