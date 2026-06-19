# Public Campaign Page Design

## Context

- Embedded elements (button, form, popup) and checkout modals are installed on client websites.
- The existing hosted donation form at `/donate/campaign/{campaign:form_parameter}` is hosted on `ihsan.test` but is currently just a plain donation form and is not actively used.
- The goal is to repurpose this hosted page into a **public campaign landing page** that feels like a real campaign page rather than a raw donation form.

## Goal

Create a public-facing campaign page hosted on `ihsan.test` that:

- Presents campaign information (title, description, image, fundraising progress).
- Provides a clear call-to-action to donate.
- Opens the existing checkout modal for payment, consistent with the embedded-element experience.
- Is shareable via URL, QR code, WhatsApp, etc.

## Proposed Approaches

### Approach A: Repurpose the existing hosted donation route

- Convert `/donate/campaign/{campaign:form_parameter}` from a donation form into a campaign landing page.
- The page displays campaign info and a "Donate Now" button.
- Clicking the button opens the existing checkout modal (`DonationForm` in popup mode) using a default form element tied to the campaign.
- Keeps the existing URL structure and route name (`donations.campaign-show`).

**Pros:**
- No new public route needed.
- Reuses existing modal/payment flow.
- Existing share links keep working.

**Cons:**
- URL path says `/donate/...` instead of `/campaign/...`.
- Need a default element for each campaign (or create one on demand) to drive the modal.

### Approach B: New campaign public page route

- Create a new route such as `/campaigns/{campaign:public_id}` or `/c/{campaign:slug}`.
- This route renders a standalone public campaign landing page.
- The "Donate" button opens the checkout modal using the campaign’s default form element.
- Keep `/donate/campaign/{form_parameter}` as is or redirect it to the new campaign page.

**Pros:**
- Cleaner, more conventional URL (`/campaigns/...`).
- Separates the campaign marketing page from the donation form.

**Cons:**
- Adds another public route.
- Existing share/embed links may need updating or redirects.

## Recommended Approach

**Approach A — repurpose the existing hosted page.**

Because the hosted route already exists and is not being used as a pure donation form, it is the lowest-friction path to a public campaign page. The URL can be kept as `/donate/campaign/...` for now (it is still a donation entry point) or aliased later.

## Components

- `App\Livewire\CampaignPublicPage` (new class-based or SFC page component)
  - Resolves the campaign by `form_parameter`.
  - Authorizes public access for active campaigns only.
  - Displays campaign title, description, image, fundraising progress, and status.
  - Renders a "Donate Now" CTA.
- Reuse `App\Livewire\DonationForm` for the checkout modal.
  - Use `isPopup = true` and an appropriate element.
- Layout: `layouts.donation` (clean, minimal) or a new `layouts.public`.

## Data Flow

1. Request hits `/donate/campaign/{form_parameter}`.
2. `CampaignPublicPage` loads the campaign and its organization.
3. If campaign is not active, return 404.
4. Page renders campaign info and a donate button.
5. User clicks donate → open checkout modal with `DonationForm`.
6. Payment success → modal closes automatically (existing popup behavior).

## Open Questions

- Should every campaign have a default form element auto-created for modal purposes?
- Should the page progress bar be real-time or static on first render?
- Should the URL use the campaign `slug`, `public_id`, or keep `form_parameter`?

## Testing

- Feature test: active public campaign page renders expected info and donate button.
- Feature test: draft/archived campaign returns 404.
- Feature test: clicking donate opens the checkout modal.
- Feature test: donation success closes the modal.
