# LinkedIn, X Ads, and Snapchat Tracking Design

## Goal
Add browser-pixel and server-side conversion tracking for LinkedIn, X (Twitter) Ads, and Snapchat to the existing advertising-pixel framework.

## Context
The application already supports Meta (browser pixel + Conversion API), Google Analytics 4, Google Ads, and TikTok. The implementation follows a consistent three-layer pattern:

1. `TrackingProvider` enum defines credentials and toggle options.
2. `TrackingScriptService` transforms DB configs into a frontend-safe config payload.
3. `resources/views/components/tracking-scripts.blade.php` loads each platform's base tag and exposes a single `window.IhsanTrack(eventName, payload, opts)` helper.
4. Server-side queued jobs dispatch conversion events to each platform's API.

This design extends that pattern to LinkedIn, X Ads, and Snapchat.

## Providers and Events

| Provider | Browser Base Tag | Browser Checkout Event | Browser Purchase Event | Server API Endpoint | Server Auth |
|----------|------------------|------------------------|------------------------|---------------------|-------------|
| LinkedIn | LinkedIn Insight Tag (`lnkd`) | `lintrk('track', { conversion_id })` | `lintrk('track', { conversion_id, ... })` | `https://api.linkedin.com/rest/conversions` | `access_token` |
| X Ads | X Pixel (`twq`) | `twq('event', conversion_id)` | `twq('event', conversion_id, ...)` | `https://ads-api.twitter.com/12/measurement/conversions/events` | `access_token` |
| Snapchat | Snap Pixel (`snaptr`) | `snaptr('track', 'START_CHECKOUT', ...)` | `snaptr('track', 'PURCHASE', ...)` | `https://tr.snapchat.com/v2/conversion` | `access_token` + `pixel_id` |

All three platforms will respond to:
- `IhsanTrack('InitiateCheckout', { value, currency, ... })`
- `IhsanTrack('Purchase', { value, currency, ... }, { eventID })`

Page-view events are emitted by the base tag when enabled, controlled by the `track_page_views` option where each platform supports it.

## Credential and Option Fields

### LinkedIn
Credentials:
- `partner_id` — LinkedIn partner ID for the insight tag.
- `conversion_id` — Default conversion ID used for both checkout-start and purchase browser events and for server-side conversions.
- `access_token` — OAuth access token for server-side Conversion API calls.

Options:
- `track_page_views` — Load the insight tag base code.
- `track_donation_starts` — Fire `InitiateCheckout`/start conversion.
- `track_conversions` — Fire completed-donation conversion.

### X Ads
Credentials:
- `pixel_id` — Pixel ID for the base tag.
- `conversion_id` — Conversion event ID (`tw-o1234-abcde` format).
- `access_token` — Bearer token for X Ads conversion API.

Options:
- `track_page_views` — Load the X Pixel base code.
- `track_donation_starts` — Fire checkout-start conversion event.
- `track_conversions` — Fire completed-donation conversion event.

### Snapchat
Credentials:
- `pixel_id` — Snapchat Pixel ID.
- `access_token` — Conversion API token.

Options:
- `track_page_views` — Load the Snap Pixel base code.
- `track_donation_starts` — Fire `START_CHECKOUT` event.
- `track_conversions` — Fire `PURCHASE` event.

## Files Changed

### Back-end
- `app/Enums/TrackingProvider.php` — extend credential and option fields for the three providers.
- `app/Services/TrackingScriptService.php` — add `buildLinkedInConfig`, `buildXAdsConfig`, `buildSnapchatConfig`.
- `app/Jobs/SendLinkedInConversionEvent.php`
- `app/Jobs/SendXAdsConversionEvent.php`
- `app/Jobs/SendSnapchatConversionEvent.php`
- `app/Jobs/ProcessStripeWebhook.php` — dispatch the three new jobs where `SendMetaConversionEvent` is dispatched.
- `app/Livewire/DonationForm.php` — dispatch the three new jobs where `SendMetaConversionEvent` is dispatched.
- `database/factories/TrackingConfigurationFactory.php` — add `linkedin`, `xAds`, `snapchat` factory states.

### Front-end
- `resources/views/components/tracking-scripts.blade.php` — add base tags and `IhsanTrack` handlers for each provider.

### Tests
- `tests/Feature/LinkedInTrackingTest.php`
- `tests/Feature/XAdsTrackingTest.php`
- `tests/Feature/SnapchatTrackingTest.php`

## Error Handling

- Jobs return early if the provider config is missing, disabled, or the relevant option is off.
- Jobs return early if the required `access_token` is empty.
- HTTP calls use a timeout and connect timeout. Failures are caught, a `TrackingEvent` record is persisted with status `failed`, and the exception is re-thrown so the queue can retry.
- Successful calls create a `TrackingEvent` record with status `sent`.

## Testing Strategy

Each platform gets the same test coverage as Meta Pixel:
- Base script is injected when the provider is configured and enabled.
- Base script is not injected when disabled or unconfigured.
- `IhsanTrack` emits the correct literal event names for checkout-start and purchase.
- The conversion job sends the expected HTTP request.
- Failed API responses are recorded.
- Missing access token results in no HTTP request and no `TrackingEvent`.
- Jobs dispatch from the donation success flow.

## Out of Scope

- Deduplication between browser and server events is handled the same way as Meta (event IDs), not extended with platform-specific deduplication features.
- Advanced audience/retargeting parameters; only value, currency, transaction/order ID, and hashed donor email where supported.
- Non-donation events (e.g., add_to_cart, signup).
