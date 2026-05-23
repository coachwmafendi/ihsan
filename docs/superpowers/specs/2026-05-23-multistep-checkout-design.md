# Multi-Step Checkout Flow — Design Spec

**Date:** 2026-05-23
**Status:** Approved
**Goal:** Refactor donation form from single-page to 3-step checkout flow inspired by Fundraise Up

---

## Decisions

| Decision | Choice |
|----------|--------|
| Steps | 3 (Amount → Details → Payment) |
| Progress indicator | Text only: "Step 1 of 3 — Choose Amount" |
| After payment | Thank you screen within modal |
| Back navigation | Yes — back button on Step 2 and 3 |
| Mobile layout | Hide left panel on mobile; right panel full width |
| Implementation approach | Alpine.js step state (no Livewire roundtrips for navigation) |

---

## Architecture

### State (Alpine `donationForm`)

Add to existing Alpine data:

```js
currentStep: 1,   // 1 | 2 | 3 | 'success' | 'error'
stepErrors: {},   // client-side validation errors per field
```

No new PHP properties in `DonationForm.php`. Step navigation is pure client-side.

### Files Changed

- `resources/views/livewire/donation-form.blade.php` — major UI rewrite
- `app/Livewire/DonationForm.php` — minimal (no step logic added)
- No migrations, no new models

---

## UI Per Step

### Step 1 — Amount & Frequency
- Progress: `Step 1 of 3 — Choose Amount`
- Give once / Monthly toggle
- Amount grid (3×2 suggested amounts, changes per frequency)
- Custom amount input
- CTA: "Continue →"

### Step 2 — Your Details
- Progress: `Step 2 of 3 — Your Details`
- Back button (top left, subtle)
- Fields: Name (required), Email (required), Phone (optional)
- Conditional: dedication checkbox, comment textarea (per element config)
- CTA: "Continue →"

### Step 3 — Payment
- Progress: `Step 3 of 3 — Payment`
- Back button
- Summary bar: "RM120 · Monthly" (compact, above card element)
- Stripe card element (existing `#card-element`)
- CTA: "Donate monthly" / "Donate once" (dynamic per frequency)

### Success Screen
- Checkmark icon (emerald)
- "Thank you, [name]!"
- "Receipt sent to [email]"
- Close button for popup; static message for embed/standalone

### Error Screen
- X icon (red) + Stripe error message
- "Try again" button → returns to Step 3, preserves amount and donor details

---

## Validation

### Client-side (Alpine — before step transition)

```js
validateStep1(): amount > 0 && amount <= 100000
validateStep2(): name.trim() !== '' && isValidEmail(email)
```

Errors shown inline below each field. Cleared on input change.

### Server-side (Livewire — on submit)

Laravel validation rules unchanged. If Livewire returns validation error, show at Step 2 or Step 3 without resetting step.

---

## Submit Flow (Step 3)

1. Alpine: `stripe.createPaymentMethod()` → `paymentMethod.id`
2. Alpine: `$wire.$set()` all fields → `$wire.submit()` → `clientSecret`
3. Alpine: `stripe.confirmCardPayment(clientSecret, { payment_method: paymentMethod.id })`
4. On success: `$wire.confirmPayment(paymentIntent.id)` → DB update
5. Alpine: `currentStep = 'success'`
6. On Stripe error: `currentStep = 'error'`, keep form data intact

---

## Layout

### Desktop Popup
```
┌─────────────────────────────┬──────────────────┐
│  Left panel (static)        │  Right panel      │
│  - Campaign image           │  Step indicator   │
│  - Org logo + title         │  Step content     │
│  - Description              │  CTA button       │
│  - Progress bar (if target) │                   │
└─────────────────────────────┴──────────────────┘
```

Left panel: static across all steps.

### Mobile (< lg breakpoint)
```
┌──────────────────┐
│  Step indicator  │
│  Step content    │
│  CTA button      │
└──────────────────┘
```

Left panel hidden on mobile. Right panel full width.

---

## Out of Scope

- Dedication/tribute feature (config-driven, existing behaviour kept)
- Anonymous donation toggle
- UTM params (handled server-side, no UI change)
- FPX / DuitNow payment methods (V2)
