# Campaign Page Tab & Public Campaign Page Design

## Context

- Embedded elements (button, form, popup) and checkout modals are installed on client websites.
- The existing hosted donation form at `/donate/campaign/{campaign:form_parameter}` is hosted on `ihsan.test` but is currently just a plain donation form and is not actively used.
- Administrators need a dedicated space to configure a public campaign landing page hosted by Ihsan.

## Goal

1. Add a **Campaign Page** tab inside the campaign edit screen.
2. Provide a left-hand sub-navigation with sections (starting with **Thank you screen**; others can be built iteratively).
3. Allow admins to configure the donor-facing page and post-donation behavior:
   - Thank you screen: default message or redirect to a URL.
   - Sharing settings: enabled channels, public page URL, default sharing message.
4. Build the public campaign landing page that uses these settings.

## Admin UI: Campaign Page Tab

### Tab placement

Insert between **Checkout Modal** and **Embed & Share** in the campaign edit tabs:

`Overview | Settings | Checkout Modal | Campaign Page | Embed & Share | Actions`

### Sub-navigation (left sidebar)

- Content *(placeholder for future)*
- Campaign progress *(placeholder for future)*
- Supporter impact *(placeholder for future)*
- Multiple designations *(placeholder for future)*
- Benefits *(placeholder for future)*
- **Thank you screen** *(implemented first)*

### Thank you screen section

#### 1. Post-donation behaviour

Two radio options:

- **Show supporters the default thank you screen**
  - If selected, donors see the built-in success screen after payment.
  - The success screen text can be customized with a **Thank you message** textarea.
- **Redirect supporters to a specific URL**
  - If selected, a **Redirect URL** input appears.
  - After a successful donation, the hosted campaign page redirects to this URL.
  - For checkout modals/popups, this redirects the parent page.

#### 2. Sharing channels

Checkboxes to enable/disable share buttons on the thank-you screen:

- Facebook
- X (Twitter)
- LinkedIn
- Email

#### 3. Sharing URL

Read-only or editable input showing the campaign public page URL, e.g.

```
https://ihsan.test/campaigns/{public_id}
```

A copy button should be provided.

#### 4. Default sharing message

Textarea (max 280 characters) used as the pre-filled text when donors share the campaign.

## Data Storage

Reuse existing columns where possible:

- `thank_you_message` — text shown on the default thank-you screen.
- `redirect_url` — URL to redirect after donation when redirect option is selected.

Add new config keys inside `campaigns.config` JSON:

- `post_donation_mode` — `"default"` or `"redirect"`
- `share_channels` — array of enabled channel keys, e.g. `["facebook", "x", "linkedin", "email"]`
- `share_message` — string, default sharing message

## Public Campaign Page

### Route

`/campaigns/{campaign:public_id}`

A new public route for the campaign landing page.

### Layout

Use a clean public layout (`layouts.public`) or adapt `layouts.donation`.

### Page content

- Campaign title
- Campaign description
- Campaign image (if set)
- Fundraising progress (target amount, raised so far, percentage)
- Status badge
- Primary CTA: **Donate Now**
- The donate button opens the existing checkout modal using the campaign’s default form element.

### Post-donation behaviour

The public page feeds the same `DonationForm` component. After payment success:

- If `post_donation_mode` is `"redirect"` and `redirect_url` is set, redirect the browser to `redirect_url`.
- Otherwise show the default thank-you screen with the configured `thank_you_message`.

### Sharing

On the thank-you screen, show share buttons for enabled channels using the configured `share_message` and the public page URL.

### Fallback route

Keep `/donate/campaign/{campaign:form_parameter}` working for existing links, optionally redirecting to the new public page route.

## Components

- `App\Livewire\App\Campaigns\CampaignEdit` — add `activeTab` value `campaign-page` and sub-section state.
- `App\Livewire\CampaignPublicPage` — new public page component.
- Reuse `App\Livewire\DonationForm` for checkout modal.

## Testing

- Feature test: Campaign Page tab renders with Thank you screen section.
- Feature test: switching post-donation mode toggles fields.
- Feature test: saving campaign persists `thank_you_message`, `redirect_url`, and config keys.
- Feature test: public campaign page returns 200 for active campaigns and 404 for inactive campaigns.
- Feature test: public campaign page shows campaign info and donate button.
- Feature test: successful donation on public page respects redirect or default thank-you setting.
