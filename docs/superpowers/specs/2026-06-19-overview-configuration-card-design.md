# Campaign Edit — Overview Configuration Card

## Context

The campaign edit page at `/app/campaigns/{public_id}/edit` has five top-level tabs:

1. Overview — read-only summary: stats, recent donations, details, linked elements
2. Settings — basic info, goal, end date, post-donation message
3. Checkout Modal — currency, frequency, suggested amounts, minimum amount, processing fee, comment, phone
4. Embed & Share — campaign URL, QR code, WhatsApp, embed snippets
5. Actions — archive, duplicate, delete

Settings and checkout configuration are spread across two tabs. Admins viewing Overview cannot quickly audit what has been configured without switching tabs.

## Goal

Display a compact, read-only Configuration snapshot inside the Overview tab so admins can see, at a glance, every setting/config value configured for the campaign, including defaults.

## Design

### Placement

Add a new card titled Configuration in the right-hand column of the Overview tab, positioned between the existing Details card and the Linked Elements card.

### Content and Grouping

The card is divided into clearly labelled sections.

#### Goal and Duration

- Target: from `has_target` and `target_amount`, or "No target"
- Ends: from `has_end_date` and `end_date`, or "No end date"

#### Donation Options

- Recurring: from `allow_recurring`
- Custom amount: from `allow_custom_amount`
- Minimum amount: from `minimum_amount`, or "None"

#### Checkout Defaults

- Default frequency: from `config.default_frequency`, defaulting to `one_time`
- Default amount: from `config.default_amount`, defaulting to campaign default for the currency
- Default currency: from `config.default_currency`, defaulting to accepted currency
- Currency auto-detect: from `config.currency_autodetect`, defaulting to false

#### Checkout Fields

- Cover fee: from `config.allow_cover_fee`, defaulting to true
- Comment: from `config.show_comment`, defaulting to true
- Phone: from `config.show_phone`, defaulting to true

#### Post Donation

- Thank-you message: "Set" if `thank_you_message` is present, otherwise "Not set"
- Redirect URL: from `redirect_url`, or "None"

#### Suggested Amounts

- One-time: up to six values from `config.suggested_amounts_by_currency[currency].one_time`, falling back to legacy `suggested_amounts_one_time`
- Monthly: up to six values from `config.suggested_amounts_by_currency[currency].monthly`, falling back to legacy `suggested_amounts_monthly`

### Visual Treatment

- Boolean or on-off values use small pill badges:
  - On, Enabled, Allowed → green pill
  - Off, Disabled, Not allowed → muted slate pill
- Scalar values such as amounts, dates, and URLs are shown in plain text.
- Empty or unset scalar values show a muted "None" or "Not set".
- Section labels are small, uppercase, and muted to match the existing Details card style.

### Quick Edit Links

Two text links appear in the card header:

- Edit Settings — switches to the Settings tab
- Edit Checkout — switches to the Checkout Modal tab

The tabs are controlled by Alpine.js using `@entangle('activeTab')`. The links should update `activeTab` without a full page reload, either by dispatching a browser event or by interacting with the Alpine store.

## Out of Scope

- Editing config directly from the Overview tab.
- Showing configuration history or audit logs.
- Hiding default values; defaults must be visible.

## Files Likely Affected

- `resources/views/livewire/app/campaigns/edit.blade.php` — add the Configuration card in the Overview tab.
- `app/Livewire/App/Campaigns/CampaignEdit.php` — optional helper methods to format config values if the view logic becomes verbose.

## Testing Notes

- Render the card when every config value is at its default.
- Render the card when no config JSON is stored, using defaults from mount.
- Confirm suggested amounts fall back to legacy columns when `suggested_amounts_by_currency` is empty.
- Verify quick-edit links switch the active tab without a full page reload.
