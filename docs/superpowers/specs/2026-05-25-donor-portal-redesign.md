# Donor Portal Redesign

**Date:** 2026-05-25  
**Status:** Approved  

## Summary

Redesign all 5 donor portal Blade views (`resources/views/donor/`) with a Bold Minimal aesthetic. No backend changes — pure frontend rework of existing views.

---

## Design System

**Style:** Bold Minimal  
**Background:** `#f8fafc` (page), `#ffffff` (cards)  
**Type:** `-apple-system, BlinkMacSystemFont, 'Inter', sans-serif`  
**Primary text:** `#0f172a` (slate-900)  
**Secondary text:** `#64748b` (slate-500)  
**Muted text:** `#94a3b8` (slate-400)  
**Accent green:** `#10b981` (emerald-500), `#047857` (emerald-700)  
**Borders:** `#e2e8f0` (slate-200), 1.5px  
**Border radius:** 10px cards, 8px inputs, 20px badges  
**Font weights:** 900 hero numbers, 800 headings, 700 labels/amounts, 600 secondary labels, 500 nav links  

---

## Layout — `layout.blade.php`

### Top Navigation
- Full-width dark bar: `background: #0f172a`
- Left: `Ihsan.` wordmark — white, 14px, font-weight 900, letter-spacing -0.02em
- Centre: 3 nav links — Dashboard · Donations · Subscriptions
  - Active: `background: rgba(16,185,129,0.15)`, `border: 1px solid rgba(16,185,129,0.3)`, text `#10b981`, weight 700
  - Inactive: text `rgba(255,255,255,0.4)`, weight 500
- Right: Donor initials avatar — 28px circle, `background: rgba(255,255,255,0.1)`, white text 10px 700
- Padding: 12px 16px

### Page Shell
- `background: #f8fafc`
- `max-width: 768px`, `mx-auto`, `px-6 py-8`
- Page title: 22px, font-weight 900, letter-spacing -0.02em, color `#0f172a`
- Subtitle: 12px, color `#64748b`, margin-top 2px

### Success Banner
- `background: #ecfdf5`, `border: 1.5px solid #d1fae5`, `color: #047857`, border-radius 10px, px-5 py-3, text 13px 600

---

## Pages

### `login.blade.php` (standalone, no layout)

- Full-page: `min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center`
- Card: white, border 1.5px `#e2e8f0`, border-radius 16px, padding 32px, max-width 320px, shadow `0 4px 24px rgba(15,23,42,0.08)`
- Logo: `Ihsan.` — 22px, font-weight 900, letter-spacing -0.03em, color `#0f172a`
- Tagline: "Your giving, your way." — 11px, `#94a3b8`, margin-bottom 20px
- Title: "Welcome back" — 16px, weight 800
- Description: 11px, `#64748b`, line-height 1.5
- Input: border 1.5px `#e2e8f0`, border-radius 8px, padding 9px 12px, focus ring `rgba(16,185,129,0.12)` + border `#10b981`
- Submit button: `background: #0f172a`, white text, border-radius 8px, padding 10px, weight 700, full width, hover `#1e293b`

### `dashboard.blade.php`

**Stats row** (3 cards, equal columns):
- Total Given — value in `#047857`
- Active Subscriptions — value in `#0f172a`
- Monthly Recurring — value in `#047857`
- Card: white, border 1.5px `#e2e8f0`, border-radius 10px, padding 12px
- Label: 8px, weight 700, uppercase, letter-spacing 0.08em, `#64748b`
- Value: 18px, weight 900

**Monthly Giving chart** (full width):
- White card, border 1.5px `#e2e8f0`, border-radius 10px, padding 16px
- Title: 11px, weight 700, `#0f172a`
- Keep Chart.js bar chart — bars `#f1f5f9` for past months, `#10b981` for current month
- Remove chart border, set grid color `#f1f5f9`, remove legend

**Two-column bottom grid:**

Left — "By Campaign":
- White card, border 1.5px `#e2e8f0`, border-radius 10px, padding 12px
- Each row: coloured dot (emerald/blue/purple cycle) + campaign name + RM amount
- Labels 9px weight 600 `#374151`, amounts 9px weight 800 `#0f172a`

Right — "Recent Activity":
- White card, border 1.5px `#e2e8f0`, border-radius 10px, padding 12px
- Each row: campaign name (9px weight 700) + relative time (7px `#94a3b8`) + amount (10px weight 900)
- Rows separated by 1px `#f1f5f9` border-top

### `donations.blade.php`

**Stats row** (2 cards):
- Total Given + Donations count — same card style as dashboard

**Donation cards** (one per donation):
- White card, border 1.5px `#e2e8f0`, border-radius 10px, padding 14px, margin-bottom 8px
- Top row: campaign name (11px weight 700) + org (9px `#64748b`) left | amount (14px weight 900) + date (8px `#94a3b8`) right
- Badge row: status badge + type badge
  - Succeeded: `bg-emerald-50 text-emerald-700 border-emerald-200`
  - Pending: `bg-amber-50 text-amber-700 border-amber-200`
  - Failed: `bg-red-50 text-red-600 border-red-200`
  - Refunded: `bg-slate-50 text-slate-600 border-slate-200`
  - Recurring: `bg-blue-50 text-blue-700 border-blue-200`
  - One-time: `bg-amber-50 text-amber-700 border-amber-200`
- Badge: border-radius 20px, px-2.5 py-1, font-size 8px, weight 700, border 1px

**Empty state:**
- Centered: icon circle `bg-slate-100` + title 13px weight 700 + description 11px `#64748b`

**Pagination:** default Laravel links, no changes needed

### `subscriptions.blade.php`

**Subscription cards:**
- White card, border 1.5px `#e2e8f0`, border-radius 10px, padding 14px, margin-bottom 8px
- Top row: campaign name + org left | `RM X/interval` right + next billing date (8px `#94a3b8`)
- Footer row: status badge left | cancel button right (active only)
  - Cancel button: `bg-red-50 text-red-600 border-red-200`, border-radius 6px, px-3 py-1.5, 9px weight 700
  - Cancelled card: opacity 70%, no cancel button, grey badge
- Active badge: `bg-emerald-50 text-emerald-700 border-emerald-200` with `●` prefix
- Cancelled: `bg-slate-50 text-slate-600 border-slate-200`
- Past Due: `bg-red-50 text-red-600 border-red-200`
- Paused: `bg-amber-50 text-amber-700 border-amber-200`

**Empty state:** same pattern as donations

---

## Scope

- Edit only: `resources/views/donor/layout.blade.php`, `login.blade.php`, `dashboard.blade.php`, `donations.blade.php`, `subscriptions.blade.php`
- No PHP changes, no new routes, no new controllers
- Chart.js kept via CDN (already in dashboard)
- TailwindCSS v4 utility classes throughout
- Donor name initials extracted from `$donor->name` for avatar (first letter of first + last word)
