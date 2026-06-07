# Virtual Terminal Design Spec

**Date:** 2026-06-07  
**Status:** Approved  
**Scope:** In-app virtual terminal for NGO admins to process in-person/over-the-phone donations

---

## 1. Overview

Virtual Terminal (VT) lets NGO admins manually create donations or subscriptions on behalf of supporters. It is a Filament App Panel page accessible only to organization-scoped users.

**Entry points:**
- Sidebar navigation: `Virtual Terminal`
- Preloaded supporter: `/app/virtual-terminal?vt-supporter=DRW634MU`
  - `Make donation` link on `ViewDonor` sidebar opens VT with supporter pre-filled

---

## 2. Access Control

- **Role:** Any user with `organization_id` (i.e., logged-in NGO admin in App Panel)
- **Not accessible to:** Platform super-admins in the admin panel (`Filament/Resources/Organizations`)
- **Authorization check:** Standard Filament App Panel auth gate

---

## 3. UI Layout

### 3.1 Structure

Two-column layout, left scrollable form + right sticky summary card.

```
├─ Main content (max-w-7xl) ─────────────────────────┤
│ Title: "Virtual Terminal"
│ Subtitle: "Use the Virtual Terminal to process an in-person or over the phone donation."
│
│ ├─ Left column (lg:col-span-2) ─┤  ├─ Right column (lg:col-span-1) ─┤
│ │                               │  │                                  │
│ │ Campaign                      │  │   Summary (sticky)               │
│ │ Donation                      │  │   ──────────────────             │
│ │ Supporter                     │  │   One-time donation   MYR 0.00   │
│ │ Payment method                │  │   ──────────────────             │
│ │ Transaction costs             │  │   Total amount        MYR 0.00   │
│ │                               │  │   [Make a donation]              │
│ └───────────────────────────────┘  │                                  │
└────────────────────────────────────┴──────────────────────────────────┘
```

### 3.2 Sections Detail

#### Campaign
- `Select::make('campaign_id')` — list of active campaigns scoped to current user's organization
- Default: most recently created active campaign
- Validation: required

#### Donation
- `Select::make('frequency')` — options: `once` | `monthly`
- `TextInput::make('amount')` — numeric, min 1.00, prefix `MYR`
- `DateTimePicker::make('scheduled_for')` — default to `now()`
  - Disabled when frequency = monthly (always starts now)
- Validation: amount required, numeric, >= 1.00

#### Supporter

**Mode A — Preloaded from URL (`?vt-supporter={public_id}`):**
- Fetch donor by `public_id`
- Show yellow banner:
  - Name, last donation date (relative), email, public_id copy button
  - Dismiss button (✕) clears preloaded state and switches to Mode B
- Auto-fill fields (editable):
  - First name / Last name (split from `donor.name`)
  - Receipt email (from `donor.email`)

**Mode B — Manual input:**
- `TextInput::make('first_name')` — required
- `TextInput::make('last_name')` — required
- `TextInput::make('email')` — required, email validation
- When email is blurred:
  - Backend search: `Donor::where('email', $email)->whereHas('donations.campaign', fn => organization match)->first()`
  - If found → show suggestion banner: "We found an existing supporter [Name]. Load their details?"
  - If user clicks Load → populate fields + load saved cards

**Donor resolution on submit:**
1. Match by email + org scope
2. If found → use that donor
3. If not found → create new `Donor` with filled fields
4. If new donor has no `stripe_customer_id` → create Stripe Customer

#### Payment Method

**If donor has `stripe_customer_id`:**
- Fetch saved cards from Stripe: `PaymentMethod::all(['customer' => $customerId, 'type' => 'card'])`
- Radio list for each saved card:
  - Format: `VISA ••7314 • Exp. 12/30`
  - Icon inferred from `card.brand`
- Radio: `New credit card` (default if no saved cards)

**If no Stripe customer or "New credit card" selected:**
- Show Stripe Card Element (guided by `resources/views/stripe/card-element.blade.php` pattern)
- Fields: Card number, Expiration date, CVC
- These fields are client-side Stripe Elements, not standard form inputs
- On submit, Stripe.js creates a `payment_method` token server-side or via frontend

#### Transaction Costs
- Show estimated Stripe processing fee
- Formula: `amount * (processing_fee_percent / 100)`
- Fee percent from `settings` table key `payment_processing_fee_percent`
- Real-time update when amount changes (Alpine.js wire-up)

#### Summary Sidebar (sticky top-8)
- One-time donation / Monthly recurring line
- Total amount (bold)
- `[Make a donation]` button
  - Disabled when: amount <= 0, campaign not selected, email invalid, no payment method selected
  - On click → mount Filament Action `processDonation`

---

## 4. Data Flow

### 4.1 One-Time Donation Flow

```
Admin fills form → clicks [Make a donation]
  │
  ▼
Page action: validate form
  │
  ▼
Resolve or create Donor
  │
  ▼
If donor has no stripe_customer_id:
  Create Stripe Customer (name, email, phone if available)
  Save stripe_customer_id to donor
  │
  ▼
If saved card selected:
  Create Stripe PaymentIntent with:
    - customer = donor.stripe_customer_id
    - payment_method = saved_card_id
    - off_session = true
    - confirm = true
    - amount = amount * 100 (sen)
    - currency = 'myr'
    - metadata = { campaign_id, donor_public_id, source: 'virtual_terminal' }
  │
  If new card selected:
    Create Stripe PaymentIntent with:
      - customer = donor.stripe_customer_id
      - automatic_payment_methods = { enabled: true }
      - confirmation_method = 'manual'
      - amount, currency, metadata same as above
    Frontend: confirm with Stripe Elements → returns payment_intent ID
  │
  ▼
PaymentIntent succeeded:
  Create Donation record:
    - donor_id, campaign_id, amount, currency='myr'
    - status = 'succeeded'
    - type = 'one_time' (from DonationType enum)
    - source = 'virtual_terminal'
    - stripe_payment_intent_id = pi_xxx
    - gross_amount = amount
    - base_amount = amount
  │
  ▼
Send receipt email (queue job)
  │
  ▼
Show success notification: "Donation of MYR X processed successfully."
Clear form (keep campaign selected)
```

### 4.2 Monthly Subscription Flow

```
Admin fills form → frequency = monthly → clicks [Make a donation]
  │
  ▼
Same donor resolution as 4.1
  │
  ▼
Get or create Stripe Price for this campaign + amount + monthly:
  Search: Price::all(['product' => campaign.stripe_product_id, 'unit_amount' => amount*100])
  If not found → create new Price
  │
  ▼
Create Stripe Subscription:
  - customer = donor.stripe_customer_id
  - items = [{ price = price_id }]
  - default_payment_method = saved_card_id (if saved card selected)
  - metadata = { campaign_id, donor_public_id, source: 'virtual_terminal' }
  │
  If new card:
  - Create SetupIntent first → attach payment method → use as default_payment_method
  │
  ▼
Subscription created/active:
  Create Subscription record:
    - donor_id, campaign_id
    - amount, currency='myr', interval='month'
    - status = 'active'
    - stripe_subscription_id = sub_xxx
    - started_at = now
  │
  ▼
Show success notification
Clear form
```

---

## 5. Stripe Integration

### 5.1 Stripe Options Pattern

All Stripe API calls must include `stripe_account` header when organization's Stripe Connect account is connected:

```php
$stripeOptions = $organization->stripe_account_id
  ? ['stripe_account' => $organization->stripe_account_id]
  : [];
```

### 5.2 API Calls

| Action | Stripe API |
|--------|-----------|
| Create customer | `Customer::create([...], $stripeOptions)` |
| List saved cards | `PaymentMethod::all(['customer' => $id, 'type' => 'card'], $stripeOptions)` |
| Create PaymentIntent | `PaymentIntent::create([...], $stripeOptions)` |
| Confirm PaymentIntent | `PaymentIntent::confirm($id, ['payment_method' => $pm], $stripeOptions)` |
| Create Price | `Price::create([...], $stripeOptions)` |
| Create Subscription | `Subscription::create([...], $stripeOptions)` |
| Create SetupIntent | `SetupIntent::create([...], $stripeOptions)` |

### 5.3 Error Handling

- Wrap all Stripe calls in try/catch
- On `CardException`: show user-friendly message ("Card declined: insufficient funds")
- On `ApiErrorException`: log full error, show generic: "Payment service error. Please try again."
- On `InvalidRequestException`: log and show: "Invalid payment request. Please check your details."
- Set Stripe API key before each call: `Stripe::setApiKey(config('services.stripe.secret'))`

---

## 6. Database Changes

No migration required. Reuses existing tables:
- `donors` — create if not exists, update `stripe_customer_id`
- `donations` — new record per one-time charge
- `subscriptions` — new record per monthly plan
- `campaigns` — read for selection

**New enum consideration:** Add `source` field to `donations` table? Could use existing metadata or add nullable `source` varchar default null. If not in scope, use `metadata` JSON or skip.

---

## 7. Files to Create / Modify

### New Files

| Path | Purpose |
|------|---------|
| `app/Filament/App/Pages/VirtualTerminal.php` | Filament page class |
| `resources/views/filament/app/pages/virtual-terminal.blade.php` | 2-column layout view |
| `app/Actions/Stripe/ProcessVirtualTerminalDonation.php` | One-time charge service |
| `app/Actions/Stripe/ProcessVirtualTerminalSubscription.php` | Subscription service |
| `app/Mail/VirtualTerminalReceipt.php` | Receipt mail (or reuse existing `DonationReceipt`) |

### Modified Files

| Path | Change |
|------|--------|
| `app/Filament/App/Resources/Donors/Pages/ViewDonor.php` | Add `getDonorPortalUrl()` (already exists) + update sidebar links |
| `resources/views/filament/app/resources/donors/pages/view-donor.blade.php` | Update `Make donation` link to VT with supporter param |
| `routes/web.php` | Register VT page route if needed (Filament auto-routes pages) |

---

## 8. Testing Plan

| Test | Type |
|------|------|
| VT page accessible by org admin | Feature |
| VT page preloads supporter from query param | Feature |
| VT page hides from non-org users | Feature |
| One-time donation creates Donation record | Feature |
| Monthly subscription creates Subscription record | Feature |
| New donor created when email not found | Feature |
| Existing donor matched by email | Feature |
| Stripe Customer created for new donor | Feature Unit |
| Stripe PaymentIntent created for one-time | Feature Unit (mocked) |
| Stripe Subscription created for monthly | Feature Unit (mocked) |
| Receipt email queued on success | Feature |
| Form validation fails on missing fields | Feature |
| Saved cards fetched from Stripe | Feature Unit (mocked) |

---

## 9. Open Questions / Decisions

1. **Donation source tracking:** Add `source` column to `donations` table (e.g., 'virtual_terminal', 'website', 'widget') or use metadata JSON. Recommended: add nullable `source` varchar for reporting.
2. **First/last name split:** Donor model stores `name` as full string. On VT, split by first space → `first_name` + `rest`. On save, rejoin. If user edits names, full name is `first_name + ' ' + last_name`.
3. **Receipt email:** Reuse existing `DonationReceipt` mailable + PDF receipt (already built) or create new `VirtualTerminalReceipt`. Recommended: reuse existing, just set `source` flag.
4. **Stripe Connect:** All Stripe calls go through connected account if org has `stripe_account_id`.

---

## 10. UI Details

### Color scheme
- Primary action button: `bg-gray-900 text-white` (same as existing app dark buttons)
- Banner: `bg-yellow-50 border-yellow-200 text-yellow-800`
- Sticky summary card: `bg-gray-50 border-gray-200`
- Section headings: `text-base font-semibold`
- Form inputs: standard Filament/Tailwind rounded-lg borders

### Responsive
- Mobile (< lg): single column, summary moves below form
- Desktop: 2-column with sticky sidebar

### Alpine.js interactions
- Amount input → real-time update summary total
- Dismiss banner → clear preloaded supporter state
- Payment method radio → toggle saved card vs new card fields
- Email blur → debounced search for existing donor
