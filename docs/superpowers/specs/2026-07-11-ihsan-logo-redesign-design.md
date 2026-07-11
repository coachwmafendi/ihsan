# ihsan Logo Redesign — Design Spec

**Date:** 2026-07-11
**Status:** Approved by user (visual companion session, 4 rounds)

## Summary

Replace the current logo (generic four-point sparkle + teal dot) with an eight-point star
(Rub el Hizb) mark and a full-teal "ihsan" wordmark, across every asset in the app.

## Rationale

- Current sparkle mark is generic and carries no story.
- Eight-point star is classic Islamic geometry — culturally rooted without cliché,
  fits a donation platform for Malaysian NGOs/masjids.
- Chosen over alternatives (masjid arch + coin, cupped hands + heart, crescent + spark)
  and over solid/two-tone/amber-center executions during the brainstorm session.

## The Mark

Two rounded squares, one rotated 45°, stroke-only, with a filled center dot.

```svg
<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="14" y="14" width="36" height="36" rx="7" stroke="#0d9488" stroke-width="5"/>
  <rect x="14" y="14" width="36" height="36" rx="7" stroke="#0d9488" stroke-width="5" transform="rotate(45 32 32)"/>
  <circle cx="32" cy="32" r="6" fill="#0d9488"/>
</svg>
```

### Colors

| Context | Mark stroke/dot | Wordmark |
|---|---|---|
| Light mode | teal-600 `#0d9488` | teal-700 `#0f766e` |
| Dark mode | teal-400 `#2dd4bf` | teal-400 `#2dd4bf` |

In Blade components rendered inside light/dark-aware layouts, use Tailwind classes
(`text-teal-600 dark:text-teal-400`) with `stroke="currentColor"` / `fill="currentColor"`
so one component serves both modes. Static SVG files in `public/` get hardcoded hex
(separate light/dark files already exist).

### Wordmark

"ihsan", lowercase, Inter 600, `letter-spacing: -0.02em`. Rendered as `<text>` in the
static SVGs (matches current convention in `public/logo-ihsan.svg`).

### Small-size rule

At 16px (favicon.ico 16px slice, small SVG favicon rendering) the center dot turns to
mud. Small variant: **drop the center dot, thicken stroke to 8, expand squares**
(`x=12 y=12 w=40 h=40 rx=8`). 32px and above keep the standard mark.

## Asset Inventory

All references load these paths via `asset()` — replacing files in place covers the
Filament panel (`AdminPanelProvider` `->brandLogo()` / `->darkModeBrandLogo()`),
admin login page, email layouts, and `partials/head.blade.php`. No PHP/config changes.

| # | Asset | Treatment |
|---|---|---|
| 1 | `resources/views/components/app-logo-icon.blade.php` | Standard mark, `currentColor`, keeps existing `$attributes` merge + aria |
| 2 | `resources/views/components/app-logo.blade.php` | Composes `app-logo-icon`; add `text-teal-700 dark:text-teal-400 font-semibold tracking-tight` classes to the `flux:brand` / `flux:sidebar.brand` name text so the wordmark is teal (adjust selector to whatever Flux renders) |
| 3 | `public/logo-ihsan.svg` | Mark (light hex) + teal-700 wordmark, 180×64 lockup |
| 4 | `public/logo-ihsan-dark.svg` | Mark + wordmark in `#2dd4bf`, 180×64 lockup |
| 5 | `public/favicon.svg` | Small variant (no dot, stroke 8), light hex |
| 6 | `public/favicon-32x32.png` | Rendered from standard mark SVG at 32px |
| 7 | `public/favicon-180x180.png` | Rendered from standard mark SVG at 180px |
| 8 | `public/apple-touch-icon.png` | 180px render on white background (iOS masks corners; no transparency) |
| 9 | `public/favicon.ico` | 16px (small variant) + 32px (standard) slices |

PNG/ICO generation: render from SVG with whatever is available locally
(`rsvg-convert`, `sharp` via npx, or ImageMagick). One-off generation, not a build step.

## Out of Scope

- No structural changes to `flux:brand` / `flux:sidebar.brand` usage or layout markup
  (styling classes only).
- No new brand colors introduced; teal palette already in use.
- Welcome page, emails, case-study pages pick up new assets automatically via `asset()` paths.

## Testing

- Existing app panel smoke test suite must stay green (covers pages that render the logo).
- Component test: render `<x-app-logo-icon />` (Blade component render assertion),
  assert new SVG structure (two `<rect>` elements, no old star `<path>`).
- Manual: login page light/dark, Filament admin, favicon in browser tab.

## Git

Work on `dev` branch per project workflow. Never push to `main`.
