# Campaign Edit — Campaign Page Config in Overview Tab

## Context

The campaign edit page at `/app/campaigns/{public_id}/edit` has five top-level tabs:

1. Overview — read-only summary: stats, configuration, recent donations, linked elements
2. Settings — basic info, campaign formats, goal, end date
3. Checkout Modal — currency, frequency, suggested amounts, minimum amount, processing fee, comment, phone
4. Campaign Page — content, progress display, thank-you screen, sharing
5. Actions — archive, duplicate

Campaign Page settings are only visible in the dedicated Campaign Page tab. Admins viewing Overview cannot quickly audit the Campaign Page configuration without switching tabs.

## Goal

Display a compact, read-only Campaign Page configuration snapshot inside the Overview tab so admins can see, at a glance, whether the Campaign Page is enabled and how it is configured.

## Design

### Placement

Add a new card titled **Campaign Page** in the right-hand column of the Overview tab, positioned below the existing **Linked Elements** card.

### Content and Grouping

#### Disabled State

If `campaign_page_enabled` is `false`:

- Show a muted badge: **Disabled**.
- Show helper text: "Campaign Page is not enabled. Enable it in Settings > Campaign formats."
- Provide a link: **Enable Campaign Page** that switches to the Settings tab (`$set('activeTab', 'settings')`).

#### Enabled State

If `campaign_page_enabled` is `true`:

- **Status**: green badge **Enabled**.
- **Public URL**: read-only input showing the campaign page URL (`route('campaigns.public', $campaign->public_id)`) with a copy button.
- **Content Title**: display `contentTitle` if set, otherwise fallback to campaign title with a muted "(fallback)" indicator.
- **Content Message**: display the first 150 characters of `contentMessage` if set, otherwise "Not set".
- **Show Total Raised**: badge **On** if `show_total_raised` is true, otherwise **Off**.
- **Post-Donation Experience**:
  - `default`: "Default thank-you screen"
  - `redirect`: "Redirect to URL" plus the redirect URL if set
- **Thank-You Message**: "Set" if `thank_you_message` is present, otherwise "Not set".
- **Sharing Channels**: display pill badges for each selected channel in `shareChannels` (Facebook, X, LinkedIn, Email). Show "None" if empty.
- **Default Sharing Message**: display the first 150 characters of `shareMessage` if set, otherwise "Not set".
- Provide a link: **Edit Campaign Page** that switches to the Campaign Page tab (`$set('activeTab', 'campaign-page')`).

### Visual Treatment

- Boolean/on-off values use small pill badges:
  - On, Enabled, Allowed → green pill
  - Off, Disabled, Not allowed → muted slate pill
- Scalar values such as URLs and messages are shown in plain text.
- Empty or unset scalar values show a muted "None" or "Not set".
- Section labels are small, uppercase, and muted to match the existing Configuration card style.

### Quick Edit Link

The card header contains one text link:

- **Edit Campaign Page** — switches to the Campaign Page tab.

The tabs are controlled by Alpine.js using `@entangle('activeTab')`. The link updates `activeTab` via Livewire without a full page reload.

## Out of Scope

- Editing Campaign Page config directly from the Overview tab.
- Showing configuration history or audit logs.
- Hiding default values; defaults must be visible.

## Files Affected

- `resources/views/livewire/app/campaigns/edit.blade.php` — add the Campaign Page card in the Overview tab.
- `tests/Feature/CampaignEditTest.php` — extend the existing overview snapshot test to assert Campaign Page config values.

## Testing Notes

- Render the card when Campaign Page is disabled.
- Render the card when Campaign Page is enabled and all config values are set.
- Confirm fallback content title uses campaign title when `content_title` is empty.
- Confirm public URL is shown and copyable when Campaign Page is enabled.
- Verify the "Edit Campaign Page" link switches the active tab.
