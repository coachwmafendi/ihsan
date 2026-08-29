# Monthly Upsell Results — Design

**Date:** 2026-08-29
**Status:** Approved, ready for implementation planning
**Related:** [2026-08-23-monthly-upsell-design.md](2026-08-23-monthly-upsell-design.md)

## Problem

The monthly upsell ships, but nobody can tell whether donors take it. Every
donation already records three fields inside `utm_params`
([DonationForm.php:921](../../../app/Livewire/DonationForm.php)):

- `upsell_shown`
- `upsell_accepted`
- `upsell_original_amount`

Reading them means querying raw JSON by hand. Production on 2026-08-29:

| | count |
|---|---|
| donations carrying tracking params | 124 |
| `upsell_shown` | 6 |
| `upsell_accepted` | 0 |

Those 6 rows are roughly 3 donors: one retried a failed MYR 50 payment four
times within 30 seconds.

## Goal

Answer one question for a campaign manager: **is the upsell working?** Keep, tune,
or kill the feature based on real numbers rather than impressions.

## Non-goals

- Impressions from donors who see the offer and abandon before checkout. The
  offer fires at step 1, but a donation row is only created at step 2, so those
  donors leave no record. Capturing them needs an events table written from
  public traffic — a separate project, worth doing only once acceptance volume
  justifies it.
- Org-wide rollup across campaigns.
- Per-tier breakdown of which suggested amounts convert.

## What the panel shows

Four figures appended to the existing **Monthly Upsell** card on the campaign
edit page, below the current settings list.

| Figure | Definition |
|---|---|
| Offers shown | Distinct donors whose donation carries `upsell_shown` |
| Accepted | Distinct donors who switched to monthly at the offer |
| Plans started | Accepted donations that produced a subscription and succeeded |
| Added value | Sum of those plans, expressed as MYR per month |

### Counting rules

**Distinct donors, not donation rows.** Retried payments would otherwise report
6 offers where 3 people saw one.

**Accepted and Plans started stay separate.** A donor can accept and then have
the card decline. Accepted measures whether the offer persuades; Plans started
measures what is actually banked. Divergence points at payments, not copy.

**No percentage below 30 offers shown.** Under that threshold the card shows
counts only. A "0% conversion" badge derived from 3 donors reads as a verdict on
the feature when it is noise. Past the threshold, a rate renders beside the
counts.

**Empty state.** "No offers shown yet" when the campaign has no upsell history,
so an enabled-but-untested campaign does not look broken.

## Implementation

### Data source

JSONB predicates on `donations`, scoped to the campaign — for example
`where('utm_params->upsell_shown', true)`. No migration and no backfill; the
figures work against records already in production.

### `app/Services/MonthlyUpsellStats.php`

New class beside the existing `MonthlyUpsellRules`, following the same
convention. One public method takes a `Campaign` and returns a typed array:

```
array{
    offers_shown: int,
    accepted: int,
    plans_started: int,
    added_monthly_value: float,
    is_approximate: bool,
    shows_rate: bool,
}
```

Two aggregate queries, campaign-scoped. Every counting rule above lives here, so
it is testable without a Livewire component or a Blade view.

### Wiring

A `#[Computed]` property on `CampaignEdit` calls the service; the Monthly Upsell
card renders the result. The card already exists — this appends a section. No new
route, no new page, no change to the donation form or the offer logic.

### Currency

Added value sums `base_amount`, falling back to `gross_amount` when null,
matching the dashboard. If any contributing plan was in a foreign currency the
figure is prefixed `≈`, following the existing approximation convention.

## Testing

Feature test driving the service with factories:

- a donor who saw the offer and declined
- a donor who accepted and paid
- a donor who accepted whose payment failed
- the four-retry case, asserting it collapses to one donor

Plus a `CampaignEdit` test asserting the card renders the figures, and the empty
state when the campaign has no upsell history.

## Risks

- **Sample size.** At current volume no figure is statistically meaningful. The
  30-offer threshold guards against over-reading, but the counts themselves may
  still invite premature conclusions.
- **Denominator is partial.** "Offers shown" counts only donors who reached
  checkout, so the true impression count is higher and any future rate is
  optimistic. The panel wording must say so.
