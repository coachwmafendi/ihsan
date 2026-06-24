# Subscription Change Amount Modal Design

## Context

The donor portal subscriptions page currently navigates to a separate page (`/donorportal/{org}/subscriptions/{subscription}/increase`) when a donor clicks **Change Amount**. The user wants the same flow to open in a modal window on the subscriptions page for a smoother UX.

This design keeps the existing standalone increase page intact so signed email links and direct bookmarks continue to work.

## Goal

When an authenticated donor clicks **Change Amount** on the subscriptions page:

1. A modal opens inline with the increase-amount form.
2. The donor selects a preset increment or enters a custom amount.
3. On confirm, the modal submits via AJAX to the existing `change-amount` endpoint.
4. On success, the modal closes and the subscriptions list reloads so the new amount is visible.
5. On error, an inline error message is shown in the modal.
6. The standalone `/subscriptions/{subscription}/increase` page remains unchanged.

## Decision

Use an **inline Alpine.js modal** embedded directly in `resources/views/donor/subscriptions.blade.php`, following the same pattern as the existing payment-method modal.

## Architecture

### UI State

Add the following properties to the page-level Alpine `x-data` object:

- `increaseModal` — `null` when closed, subscription `public_id` when open.
- `increaseProcessing` — boolean to disable controls and show spinner.
- `increaseSuccess` — boolean to show success message before reload.
- `increaseError` — string for inline error banner.
- `increaseSelected` — currently selected preset increment or `'custom'`.
- `increaseCustomAmount` — string value of custom amount input.
- `increaseSubscription` — object with data needed for the active subscription (current amount, currency, symbol, interval, cover fee, change-amount URL).

### Trigger

Replace the current **Change Amount** anchor with a button:

```html
<button @click="openIncrease(subscription)">
    Change Amount
</button>
```

`openIncrease()` resets the form state, stores the subscription data, and sets `increaseModal` to the subscription `public_id`.

### Modal Layout

Modal markup mirrors the content of `subscription-increase.blade.php`:

- Heading: "Change donation amount"
- Subheading showing current amount.
- 4 option cards (3 presets + custom) in a responsive grid.
- Custom amount input shown when `'custom'` is selected.
- Cover-fee note if the subscription has `cover_fee` enabled.
- Confirm and Cancel buttons.
- Loading overlay while processing.
- Success state with confirmation and auto-reload after short delay.

### Submission

`submitIncrease()` builds the new total amount and posts JSON to:

```
POST /donorportal/{organization}/subscriptions/{subscription}/change-amount
```

The existing `DonorSubscriptionController::changeAmount` already supports JSON/AJAX requests and returns `{ success, new_amount }` on success or `{ error }` on failure, so the modal can reuse it without backend changes.

### Error Handling

- Validation: disable Confirm if selected custom amount is invalid or new total exceeds 99999.99.
- Server error: display returned `error` message in a red banner inside the modal.
- Network error: display a generic "Unable to update..." message.

### Success

On success:

1. Set `increaseSuccess = true`.
2. After ~1.5 seconds, call `location.reload()` so the subscriptions list reflects the new amount.

## Scope

- Only affects the authenticated subscriptions page UI.
- Standalone increase page (`subscription-increase.blade.php`) and signed `increase-link` route are untouched.
- No backend controller changes required.

## Files Involved

- `resources/views/donor/subscriptions.blade.php` — add Alpine state, trigger button, and modal markup.
- `app/Http/Controllers/DonorSubscriptionController.php` — no changes (existing endpoint reused).
- `routes/web.php` — no changes.

## Testing Notes

- Assert authenticated donor can open modal and submit new amount.
- Assert success reloads subscriptions page.
- Assert error state shows inline banner.
- Assert existing standalone `/increase` route still renders the full page.
