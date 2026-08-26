# Monthly Upsell on the Donation Form — Design

## Goal

When a donor selects a one-time gift and continues past the amount step, offer to convert that gift into a monthly donation. The offer amounts are derived from rules the organisation configures per campaign, not from a hardcoded formula and not from an LLM.

Reference behaviour: the FRU donation form shows an interstitial after the amount step — "Will you convert your RM 120 contribution into a monthly donation?" — with two monthly amounts (RM 40 and RM 60) and a decline link.

## Context

- The donation form is a Livewire component, `App\Livewire\DonationForm`, rendered by `resources/views/livewire/donation-form.blade.php`.
- Step navigation is entirely client-side, in the Alpine component `donationStep` registered by `resources/views/partials/donation-step.blade.php`. `currentStep` is `1`, `2`, `3`, or the string `'success'` / `'error'`.
- `nextStep()` advances the step, except in inline embed mode, where step 1 instead posts `ihsan:step-continue` to the parent widget, which opens the checkout modal with the chosen amount and frequency.
- `handleSubmit()` pushes local Alpine state into the Livewire component with `$wire.$set(..., false)` before calling `submit()`.
- `Campaign` already has `allow_recurring`, `minimum_amount`, `suggested_amounts_monthly`, and an array-cast `config` JSON column (all fillable).
- `DonationForm::submit()` already writes a `utm_params` array onto the donation, including `frequency` and `source`.

## Scope

### In Scope

- A pure rules service that turns a one-time amount plus a campaign's tier config into zero, one, or two monthly offers.
- An upsell panel in the donation form, shown between step 1 and step 2, in both modal/public-page mode and inline embed mode.
- A decline cooldown stored in `localStorage`.
- Suppression of a second offer when the embed has already shown one.
- A "Monthly Upsell" card and edit modal on the campaign edit page.
- Analytics keys written into the existing `utm_params` payload.
- Unit, feature, and browser tests.

### Out of Scope

- Checking whether the donor already has an active subscription. The email is only collected at step 2, after the upsell fires, so this check is deferred to a later change.
- AI or LLM-generated offer amounts or copy.
- Platform-level default rules with per-campaign override. Rules live on the campaign only.
- A live preview of the offer inside the admin UI.
- Upsell after a completed payment (thank-you page upsell).
- Refactoring `donationStep`'s positional-argument signature (tracked separately).

## Architecture

### Storage

No migration. Rules live under `campaign.config['monthly_upsell']`:

```php
'monthly_upsell' => [
    'enabled' => true,
    'cooldown_days' => 30,
    'heading' => null, // null falls back to default copy
    'body' => null,    // supports the :amount placeholder
    'tiers' => [
        [
            'min' => 50,
            'max' => 199,
            'offers' => [
                ['type' => 'percent', 'value' => 33],
                ['type' => 'percent', 'value' => 50],
            ],
        ],
        [
            'min' => 200,
            'max' => null, // no upper bound
            'offers' => [
                ['type' => 'percent', 'value' => 20],
                ['type' => 'fixed', 'value' => 100],
            ],
        ],
    ],
]
```

`type` is `percent` (a percentage of the one-time amount) or `fixed` (an absolute amount in the campaign's default currency). The first matching tier wins. A campaign with no `monthly_upsell` key behaves exactly as it does today: no upsell.

### Rules service

`app/Services/MonthlyUpsellRules.php`. No request access and no framework state — a function of its arguments. It reads `$campaign->organization->chipPaymentMethods()` for the CHIP check below; the organization relation is already eager-loaded by `DonationForm::mount()`, so this adds no query on the donation path.

```php
public function resolve(Campaign $campaign, float $amount, string $currency): ?MonthlyUpsellOffer
```

Evaluation order:

1. `config['monthly_upsell']['enabled']` is falsy, or the key is absent → `null`.
2. `$campaign->allow_recurring` is false → `null`.
3. The campaign's payment methods cannot support a subscription → `null`. Concretely: a CHIP campaign whose only enabled payment method is FPX. `submit()` already forces a monthly CHIP donation onto `card`, so an FPX-only campaign would offer a plan it cannot charge.
4. No tier matches `$amount` → `null`. A tier matches when `$amount >= min` and (`max` is null or `$amount <= max`).
5. Each offer is computed and rounded to the nearest multiple of 5.
6. Offers below `max(minimum_amount, 5)` are dropped. Offers greater than or equal to the one-time amount are dropped — a "monthly" ask that exceeds the one-time gift reads as a mistake.
7. Duplicate offers after rounding collapse to one.
8. Zero surviving offers → `null`. One or two → return them.

Hard cap of two offers. More than two produces choice paralysis, and the reference UI shows two.

The return value is a small value object, `App\Support\MonthlyUpsellOffer`, holding:

- `offers`: one or two floats, ascending
- `heading`: string
- `body`: string, with `:amount` already substituted
- `declineLabel`: string
- `cooldownDays`: int

Blade, Alpine, and the tests all consume the same shape, so a change to the tier config cannot silently drift between them.

### Config validation

`MonthlyUpsellRules::validateConfig(array $tiers): array` returns a list of error messages. The campaign edit component uses it for its validation rules. Rules:

- `min` is required and greater than zero.
- When `max` is present, `max > min`.
- Tiers may not overlap.
- A `percent` offer value is between 1 and 99.
- A `fixed` offer value is greater than zero.
- Each tier has one or two offers.
- At most six tiers.

Keeping validation in the service means the admin UI and the runtime engine cannot disagree about what a valid config is.

### Frontend

`DonationForm` gains a computed property:

```php
#[Computed]
public function monthlyUpsell(): ?array
```

It calls the service and returns the value object as an array, or `null`. It returns `null` unconditionally when the request carries `upsell=1` — see "Embed flow" below.

The blade passes the result to the Alpine component as one new trailing argument, `initialUpsell = null`. The existing signature already takes 26 positional arguments; converting it to an options object is a worthwhile cleanup but is deliberately left out of this change to keep the diff reviewable.

New Alpine state:

| Field | Purpose |
| --- | --- |
| `upsell` | The server-provided offer object, or `null` |
| `upsellShown` | Whether the panel has been displayed this session |
| `upsellAccepted` | Whether the donor took an offer |
| `upsellOriginal` | The original one-time amount, used in the decline label |

`nextStep()` gains one branch at the top of its step 1 path, after `validateStep1()` succeeds and before any other work:

```
shouldShowUpsell() = upsell !== null
                  && frequency === 'one_time'
                  && !upsellShown
                  && !declinedRecently()
```

When true: record `upsellOriginal = amount`, set `upsellShown = true`, set `currentStep = 'upsell'`, and return. When false, the existing path runs unchanged.

`currentStep` is already sometimes a string, and the "Step X of 3" indicator is guarded by `typeof currentStep === 'number'`, so the indicator hides itself with no change needed.

Three exits from the panel:

- **Accept an offer** — set `frequency = 'monthly'`, set `amount` to the offer, set `upsellAccepted = true`, then call `resumeAfterStepOne()`.
- **Decline** — write the cooldown key to `localStorage`, then call `resumeAfterStepOne()`.
- **Back chevron** — set `currentStep = 1`. `upsellShown` stays true, so the panel does not reappear in this session.

`resumeAfterStepOne()` holds what the step 1 branch of `nextStep()` does today: post `ihsan:step-continue` in inline embed mode, otherwise advance to step 2 and fire `trackInitiateCheckout()`. Extracting it keeps one implementation of that logic rather than two.

### Embed flow

In inline embed mode the iframe never reaches step 2; it hands off to the parent widget, which opens the checkout modal. So:

1. The panel renders inside the inline iframe, before the `postMessage`.
2. The `ihsan:step-continue` payload already carries `amount`, `frequency`, `currency`, and `coverFee`, so the updated values propagate as-is.
3. The payload gains `upsell: 1`. `widget.js` forwards it as a query parameter on the modal URL, and `DonationForm::monthlyUpsell()` returns `null` when that parameter is present, so the donor is not asked twice.

### Cooldown

Key: `ihsan_upsell_declined_{campaignPublicId}`, value: a timestamp. `declinedRecently()` compares it against `cooldownDays`.

Every `localStorage` access is wrapped in `try`/`catch`. A sandboxed iframe or Safari private mode can throw on access, and an uncaught throw here would break the entire donation form, not just the upsell.

### Markup

The panel reuses the existing secure-donation shell rather than introducing a new modal: a back chevron on the left with a centred title, body copy, a primary offer button, a secondary offer button, and an underlined decline link beneath them. Built from the existing `x-ui` and Flux components. No new dependencies.

### Admin UI

The campaign edit page has no modals: read-only summary cards switch `activeTab`, and the fields live in the `settings` and `checkout` tab sections. The upsell follows the same split.

- A "Monthly Upsell" summary card after the "Configuration" card: an Enabled/Disabled badge, a one-line summary per tier (`RM 50–199 → 33% & 50%`), and the cooldown. Its action button switches to the `checkout` tab.
- The editor lives at the end of the `checkout` tab section: the enable toggle, cooldown days, a tier repeater (min, max, and up to two offers each with a percent/fixed selector), and the heading and body overrides. It copies the suggested-amounts repeater already in that section.

Component state: `upsell_enabled`, `upsell_cooldown_days`, `upsell_heading`, `upsell_body`, `upsell_tiers`, plus `addUpsellTier()` and `removeUpsellTier(int $index)`, mirroring the existing `addOneTimeSuggested()` / `removeOneTimeSuggested()` methods.

### Analytics

No migration. Three keys are added to the `utm_params` array already built in `submit()`:

```php
'upsell_shown' => $this->upsellShown,
'upsell_accepted' => $this->upsellAccepted,
'upsell_original_amount' => $this->upsellOriginalAmount,
```

They are backed by three new public properties on `DonationForm`, set from `handleSubmit()` via `$wire.$set(..., false)` alongside the existing properties.

These properties are analytics-only. They never influence pricing, subscription creation, or authorisation, all of which continue to derive from the validated `amount` and `frequency`. A client that tampers with them corrupts a report, not a charge.

## Error Handling

- A malformed or partially-written `monthly_upsell` config makes `resolve()` return `null` rather than throw. A bad config must degrade to "no upsell", never to a broken donation form.
- `localStorage` failures are caught and treated as "not declined".
- When the panel is somehow reached with `upsell === null` (for example a stale client), `resumeAfterStepOne()` runs immediately.

## Testing

### Unit — `MonthlyUpsellRules`

- Tier selection, including exact `min`, exact `max`, and `max: null`.
- `percent` and `fixed` offers, and rounding to the nearest multiple of 5.
- Offers below `minimum_amount` are dropped; all dropped yields `null`.
- Offers greater than or equal to the one-time amount are dropped.
- Duplicates after rounding collapse to a single offer.
- `enabled` false yields `null`; `allow_recurring` false yields `null`.
- `validateConfig` rejects overlapping tiers, a percent of 0, and a percent of 100.

### Feature — `DonationForm`

- `monthlyUpsell()` returns `null` for an ineligible campaign.
- Accepting an offer creates a donation with `frequency = monthly` at the offer amount, and `utm_params.upsell_accepted` true.
- Declining creates a one-time donation with `upsell_shown` true and `upsell_accepted` false.
- The `upsell=1` query parameter forces `monthlyUpsell()` to `null`.

### Manual verification

The project has no Pest browser-testing plugin installed, and adding a dependency is out of scope for this change. The Alpine layer is therefore verified by hand against local campaigns, with a written checklist in the implementation plan:

- Select RM 120 one-time, continue, see the panel, take an offer, and confirm step 2 shows RM 40/month.
- Select RM 120 one-time, continue, decline, and confirm step 2 shows the one-time RM 120.
- The back chevron returns to step 1 and the panel does not fire a second time.
- Inline embed mode hands off to the checkout modal with the accepted monthly amount.

If browser coverage is wanted later, installing `pestphp/pest-plugin-browser` is a separate, approved change.

### Regression

The existing donation form suite must pass unchanged for campaigns with no `monthly_upsell` key, which is every campaign today. The feature defaults to disabled.

## Branch

`feature/monthly-upsell`, branched from `dev`.
