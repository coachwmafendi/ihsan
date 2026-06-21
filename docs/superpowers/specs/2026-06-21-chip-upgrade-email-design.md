# Design: Recurring Donation Upgrade Chips in Supporter Email

## Context
Supporters who have an active recurring donation receive a thank-you email after every subsequent successful payment (not the first-time welcome email). We want to give them a low-friction way to increase their recurring amount directly from that email.

## Goal
Add a visually prominent chip/button section to the donor recurring-payment email that lets the supporter upgrade by fixed increments of 15, 25, and 35 in the subscription's currency. Each chip links to a signed donor-portal page where the upgrade can be confirmed.

## Scope
- Modify only the `DonorRecurringPaymentNotification` email.
- Do not add chips to the first-time `DonorNewSubscriptionNotification` welcome email.
- Support MYR, USD, and SGD currency symbols and formatting out of the box.
- Links must use the existing signed `donorportal.subscriptions.increase-link` route.

## Design

### Visual approach
A centered card inside the email body, separated from the thank-you copy by a horizontal rule, with:
- A heading: "Modify your {symbol}{amount} {interval} donation"
- Three outlined chips in a row: "+ {symbol}15", "+ {symbol}25", "+ {symbol}35"
- Each chip shows the interval suffix (e.g., `/mo`) based on the subscription interval.

### Layout
- Card container: light background, 1px border, 12px–16px padding, centered text.
- Chips: inline-flex, equal width, 8px gap, teal border/text, white background.
- On narrow screens chips wrap to a second line via flex-wrap.

### Data flow
1. `DonorRecurringPaymentNotification` mailable loads `donation`, `subscription`, and `organization`.
2. It builds an array of three chip options:
   - `label`: "+ {symbol}{increment}/{interval_short}"
   - `url`: signed `donorportal.subscriptions.increase-link` route URL that expires in 7 days.
3. The view renders the chip section only when the subscription is active/recurring and the donation is recurring.
4. Clicking a chip opens the donor portal increase-link page (existing behaviour) where the supporter can review and confirm the new amount.

### Currency handling
- Use the subscription's `currency_symbol` accessor, falling back to `App\Support\Currency::symbol($currency)`.
- Increments are fixed absolute values (15, 25, 35) in the subscription's currency.
- Interval suffix is derived from `SubscriptionInterval`:
  - `monthly` → `/mo`
  - `weekly` → `/wk`
  - `yearly` → `/yr`
  - Fallback to the full interval value.

### Signed URL
- Expiration: 7 days.
- Route: `donorportal.subscriptions.increase-link`.
- Parameters: `['organization' => $organization, 'subscription' => $subscription]`.

### Edge cases
- If `subscription` or `organization` is missing, hide the upgrade section.
- If the donation is not of type `Recurring`, hide the upgrade section.
- Use HTML tables/fallback paragraphs for email-client compatibility.

## Translations
New keys in `lang/en/emails.php` and `lang/ms/emails.php` under `donor_recurring_payment`:
- `upgrade_heading`: "Modify your {amount} {interval} donation"
- `upgrade_interval_monthly`: "monthly"
- `upgrade_interval_short_monthly`: "mo"
- `upgrade_interval_short_weekly`: "wk"
- `upgrade_interval_short_yearly`: "yr"
- `upgrade_interval_short_default`: "{interval}"

## Tests
- Existing `SendDonorRecurringPaymentNotificationTest` updated to assert the upgrade section renders with correct labels and signed URLs.
- Verify chip labels are localized for Malay locale.
- Verify the section is hidden for one-time donations.

## Files affected
- `app/Mail/DonorRecurringPaymentNotification.php`
- `resources/views/emails/donor-recurring-payment-notification.blade.php`
- `lang/en/emails.php`
- `lang/ms/emails.php`
- `tests/Feature/SendDonorRecurringPaymentNotificationTest.php`
