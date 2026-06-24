# WordPress-Visible Button Embed Design

## Context

Ihsan embed widgets are served by a single script tag that fetches element configuration and renders buttons/popups/forms dynamically. This works on the frontend but leaves WordPress visual editors (Gutenberg, Elementor, Classic Editor) showing nothing, because those editors either do not execute external scripts or sandbox cross-origin iframes.

The goal is to make the **Button** element visible inside WordPress visual editors while preserving the existing modal checkout behaviour on the public site.

## Goal

For element type `button`:

1. The embed code renders a visible styled button in WordPress visual editors.
2. On the public frontend, clicking the button opens the Ihsan checkout modal.
3. If JavaScript fails to load or is blocked, the button still works as a normal link.

## Decision

Use **progressive enhancement / static HTML + JS enhancement**.

The embed snippet will contain:

- A static `<a>` element styled with inline CSS so it renders in any HTML preview.
- The existing `<script src="/e/widget.js">` tag, enhanced with a `data-enhance="true"` flag.
- The script finds the pre-rendered button, attaches a click handler, and opens the checkout modal. If no static button is found it falls back to the current dynamic rendering behaviour.

## Scope

This design covers only element type `button`. Other types (`floating_button`, `popup`, `link`, `form`, `qr_code`) will be addressed separately after this pattern is proven.

## Generated Embed Code

For a Button element, the admin copy panel should generate embed code approximately like this:

```html
<a href="https://app.test/donate/TOKEN?popup=1"
   class="ihsan-button"
   data-ihsan-token="TOKEN"
   target="_blank"
   rel="noopener"
   style="display:inline-flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;font-weight:600;line-height:1.3;white-space:nowrap;letter-spacing:.01em;color:#fff;background:#2563eb;padding:13px 32px;font-size:16px;border-radius:8px;box-shadow:0 3px 12px rgba(0,0,0,.15);font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
   <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
   <span>Donate Now</span>
</a>
<script src="https://app.test/e/widget.js" data-token="TOKEN" data-api-base="https://app.test" data-enhance="true"></script>
```

The static link contains `?popup=1` so that even without JavaScript the donor lands on a popup-friendly donation page.

## Widget.js Enhancement Mode

When a script tag includes `data-enhance="true"`:

1. Fetch element configuration from `/api/public/elements/{token}` as usual.
2. Look for an existing anchor with class `ihsan-button` and attribute `data-ihsan-token` matching the token (prefer previous sibling of the script tag).
3. If found:
   - Mark it with `data-ihsan-enhanced="true"` to avoid duplicate enhancement.
   - Attach a click listener.
   - On click `preventDefault()` and call `showCheckoutModal(el)` using the fetched configuration.
   - Do not replace the script with a new DOM element.
4. If not found:
   - Fall back to the current dynamic rendering path (`renderFromData`).

This preserves backward compatibility for existing embeds that only use `<script src="..." data-token="...">`.

## Styling Source of Truth

Inline styles in the static anchor should be generated from the same element config used by `EmbedCheckoutController::button()` and `widget.js`. The PHP/Blade layer computes colours, padding, font size, border radius, and icon SVG once, and embeds them directly in the markup. Minor hover/active effects will only be available when JavaScript runs, but the button remains fully visible and clickable without JS.

## Fallback Behaviour

- JavaScript loaded and working: click opens modal checkout.
- JavaScript blocked/failed: static link behaves as a normal anchor opening the donation page.
- Static button missing (legacy embed): script renders dynamically as today.

## Files Involved

| File | Change |
|------|--------|
| `resources/views/filament/forms/components/element-embed-snippet.blade.php` | Generate static `<a>` + script with `data-enhance="true"` for `button` type |
| `resources/js/widget.js` | Add enhancement-mode detection, anchor lookup, click handler, modal trigger |
| `app/Support/EmbedWidget.php` (optional) | Helper to keep WordPress-friendly markup construction testable |

## Test Plan

1. Create a Button element and copy its embed code.
2. Paste code into a WordPress Custom HTML block / Elementor HTML widget / Classic Editor text tab and switch to visual mode — the button should be visible.
3. Publish and view the public page — clicking the button opens the Ihsan checkout modal.
4. Disable JavaScript in browser and click the button — it should navigate to the donation page.
5. Verify existing legacy `<script>`-only embeds still render and function.

## Out of Scope

- Floating Button, Popup, Link, Form, QR Code (handled later).
- WordPress plugin / Gutenberg custom block (not required for this iteration).
- Changing the public donation page UI.

## Trade-offs

| Pros | Cons |
|------|------|
| Visible in WP visual editor immediately | Static inline styles slightly duplicate widget.js rendering logic |
| Graceful no-JS fallback | Hover/active states only available with JS |
| Minimal change to existing widget.js | Other element types still need separate solutions |
| No WordPress plugin required | |
