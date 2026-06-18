# Ihsan Logo & Favicon Design

**Date:** 2026-06-18  
**Status:** Approved

## 1. Overview

Ihsan is a donation/fundraising platform for NGOs. The existing brand asset is a plain text-only PNG (`public/logo-ihsan.png`). This design introduces a distinctive but minimal wordmark-and-icon system and a matching favicon that works across the public app, Filament admin, and embeddable widgets.

## 2. Design Direction

- **Style:** Modern minimal
- **Format:** Wordmark + icon
- **Palette:** Neutral professional with a teal accent
- **Symbol:** 8-point geometric star (khatam) representing *ihsan*—excellence, balance, and sincerity
- **Typeface:** Inter (already used in the app) at weight 600

## 3. Logo Construction

### 3.1 Mark

- An 8-point geometric star built from a 45° rotated-square pattern.
- Solid fill in the primary dark color (#1a1a1a).
- Center dot in teal accent (#0d9488) to add focus and connect to the app accent color.
- High contrast and symmetry ensure it remains legible at 16 px.

### 3.2 Wordmark

- Text: `ihsan` in lowercase.
- Font: Inter, weight 600, tracking -0.02em.
- Color: #1a1a1a for light backgrounds, #ffffff for dark backgrounds.

### 3.3 Color Values

| Role | Light Background | Dark Background |
|------|------------------|-----------------|
| Mark | `#1a1a1a` | `#ffffff` |
| Accent | `#0d9488` | `#14b8a6` |
| Wordmark | `#1a1a1a` | `#ffffff` |

The dark-background accent uses a slightly brighter teal (#14b8a6) to maintain perceived brightness on charcoal.

## 4. Variants

1. **Primary logo** — icon + wordmark, light background.
2. **Reversed logo** — icon + wordmark, dark background.
3. **Icon-only mark** — for favicon, app icon, avatar, and tight spaces.
4. **Reversed icon-only mark** — for dark favicon/app icon.

## 5. Deliverables

### 5.1 New Asset Files

| File | Description |
|------|-------------|
| `public/logo-ihsan.svg` | Primary logo (icon + wordmark) |
| `public/logo-ihsan-dark.svg` | Reversed logo for dark backgrounds |
| `public/favicon.svg` | SVG favicon (icon-only) |
| `public/favicon-32x32.png` | 32 px PNG favicon |
| `public/favicon-180x180.png` | 180 px touch icon |

### 5.2 Updated Components

| File | Change |
|------|--------|
| `resources/views/components/app-logo.blade.php` | Use inline SVG wordmark + icon |
| `resources/views/components/app-logo-icon.blade.php` | Use inline SVG icon-only |
| `resources/views/vendor/filament-panels/components/logo.blade.php` | Use SVG icon-only or full logo |
| `resources/views/layouts/app.blade.php` (or equivalent) | Add `<link rel="icon">` and Apple touch icon links |

### 5.3 Removed/Legacy

- `public/logo-ihsan.png` is replaced by SVG assets. It may be kept temporarily for backwards compatibility but should not be referenced by new components.

## 6. Usage Guidelines

- **Clear space:** keep at least the height of the icon between the logo and other elements.
- **Minimum size:** do not display the full logo smaller than 100 px wide; use icon-only below that.
- **Do not** stretch, rotate, change colors outside the approved palette, or place the light logo on a light background.
- Favicon size must be legible at 16 px, so avoid extra detail in the icon-only mark.

## 7. Implementation Notes

- Favicons will be generated from the SVG source using a rendering tool (e.g., ImageMagick, cairosvg, or Playwright) to ensure crisp PNG output.
- Components should prefer inline SVG so the mark inherits current text color where appropriate.
