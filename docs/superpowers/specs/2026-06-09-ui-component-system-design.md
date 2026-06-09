# UI Component System — Design Spec

**Date:** 2026-06-09
**Scope:** `/app` Filament panel custom views (`resources/views/filament/app/`)
**Out of scope:** Donor portal (`/donorportal`), admin panel (`/admin`), auth pages

---

## Problem

Custom Blade views inside the Filament app panel repeat the same Tailwind class strings dozens of times with no abstraction. Every detail page (view-donor, view-subscription, view-donation) reimplements the same two-column layout and scroll-spy sidebar from scratch. Status badges use inline `@php match()` blocks. Section card headers are copy-pasted 12+ times.

---

## Goal

Create a thin, reusable Blade component layer under `resources/views/components/ui/` that:

- Eliminates repeated class strings from page views
- Centralises scroll-spy sidebar logic in one place
- Keeps page files readable (describe layout and data, not styles)
- Stays compatible with Filament's dark mode and Tailwind v4
- Does not touch business logic, routes, Livewire components, or Filament schema/table definitions

---

## Component Inventory

### `x-ui.section-card`

Card with icon + title header, optional inline edit action, default slot for body content.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `icon` | string | — | Heroicon component name (e.g. `heroicon-o-user`) |
| `title` | string | required | Section heading |
| `editAction` | string\|null | null | `wire:click` value for edit button |
| `editLabel` | string | `'Edit'` | Label for edit button |

**Slots:** default (body content)

**Renders:** `rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden` wrapper with `px-5 py-4 border-b` header row.

---

### `x-ui.detail-row`

Single label + value row for use inside a section-card body.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `label` | string | required | Row label |
| `labelWidth` | string | `'180px'` | CSS width for the label column |

**Slots:** default (value content — can be plain text or HTML)

**Renders:** `flex items-baseline gap-8 py-1` row with fixed-width label.

---

### `x-ui.badge`

Status/type badge pill.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `color` | string | `'gray'` | `success \| warning \| danger \| gray \| info \| blue` |

**Slots:** default (badge text)

**Color map:**
- `success` → `bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400`
- `warning` → `bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400`
- `danger` → `bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400`
- `gray` → `bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300`
- `info` → `bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400`
- `blue` → same as `info`

**Renders:** `inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium`

---

### `x-ui.stat-card`

KPI metric card used in dashboard and insights pages.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `label` | string | required | Metric label |
| `value` | string | required | Primary metric value |
| `trend` | string\|null | null | Trend text (e.g. `+8%`) |
| `trendColor` | string | `'gray'` | `success \| danger \| gray` |
| `subtext` | string\|null | null | Secondary line below value |

**Slots:** none (all data via props)

**Renders:** `rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10`

---

### `x-ui.empty-state`

Empty state placeholder — used when a section has no records.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `icon` | string | required | Heroicon component name |
| `title` | string | required | Primary message |
| `description` | string\|null | null | Supporting text |
| `variant` | string | `'dashed'` | `dashed` (border-dashed) or `clean` (no border) |

**Slots:** `action` (optional CTA button)

**Renders:** `flex items-center justify-center rounded-lg p-12` with icon circle, title, description.

---

### `x-ui.page-layout`

Two-column layout with main content area and sticky sidebar. Contains the scroll-spy Alpine.js logic — replaces duplicated observer code in view-donor, view-subscription, view-donation.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `sectionIds` | array | `[]` | Ordered list of section IDs to observe |
| `defaultSection` | string | first of sectionIds | Initially active section |

**Slots:**
- `sidebar` — sticky sidebar content (nav items + actions)
- default — main scrollable content

**Renders:** `flex gap-6` wrapper. Sidebar hidden on mobile (`hidden md:block`), sticky at `top-24`.

**Alpine data exposed to children:** `activeSection` (string), `scrollTo(id)` (function) — children bind via `x-on:click="scrollTo('id')"` and `:class="activeSection === 'id'"`.

---

### `x-ui.sidebar-nav-item`

Single nav button inside a page-layout sidebar. Reads `activeSection` from parent Alpine context.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `section` | string | required | Section ID — matched against `activeSection` |
| `icon` | string | required | Heroicon component name |

**Slots:** default (label text)

**Active state:** `bg-primary-600 text-white shadow-sm dark:bg-primary-500`
**Inactive state:** `text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50`

---

### `x-ui.sidebar-action`

Action button inside a page-layout sidebar (wire:click actions).

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `icon` | string | required | Heroicon component name |
| `variant` | string | `'default'` | `default \| danger` |
| `wire` | string\|null | null | `wire:click` value |
| `href` | string\|null | null | `href` (renders as `<a>` instead of `<button>`) |
| `target` | string\|null | null | `target` attribute when using `href` |

**Slots:** default (label text)

**Renders:** `w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors`
- `default`: `text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50`
- `danger`: `text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20`

---

### `x-ui.skeleton`

Single animate-pulse placeholder box.

**Props:**
| Prop | Type | Default | Description |
|---|---|---|---|
| `h` | string | `'4'` | Tailwind height unit (e.g. `4`, `9`, `32`) |
| `w` | string | `'full'` | Tailwind width unit or `full` |
| `rounded` | string | `'md'` | Tailwind rounded value |
| `class` | string | — | Extra classes |

**Renders:** `animate-pulse bg-gray-100 dark:bg-gray-800` with `style="height: {h}; width: {w}; border-radius: {rounded}"` using inline styles to avoid Tailwind JIT purging dynamic class strings.

---

## Execution Plan

### Phase 1 — Build components (parallel)
Build all 9 components. No page changes yet.

### Phase 2 — Pilot: refactor `view-donor.blade.php`
- Replace section card markup with `<x-ui.section-card>`
- Replace detail rows with `<x-ui.detail-row>`
- Replace two-column layout + scroll-spy with `<x-ui.page-layout>`
- Replace sidebar nav buttons with `<x-ui.sidebar-nav-item>`
- Replace sidebar action links with `<x-ui.sidebar-action>`
- Validate in browser before proceeding

### Phase 3 — Roll out to remaining pages
Apply same pattern to:
1. `view-subscription.blade.php`
2. `view-donation.blade.php`
3. `insights.blade.php` (stat-card, skeleton)
4. `insights-tabs/*.blade.php`
5. Remaining pages (`billing`, `virtual-terminal`, `pemberitahuan`, `pembayaran`, `stripe-onboarding`)

### Phase 4 — Cleanup
- Remove inline `@php match()` status badge blocks, replace with `<x-ui.badge>`
- Remove duplicate skeleton inline markup, replace with `<x-ui.skeleton>`
- Run Pint on all modified files

---

## Constraints

- Components must support Filament dark mode (all classes need `dark:` variants)
- Do not modify Filament schema, table, action, or form definitions
- Do not change any PHP class — only Blade view files
- Keep `x-filament-panels::page` or `x-filament::page` as the outer wrapper on all pages
- Components live in `resources/views/components/ui/` — no subdirectories
- Run `vendor/bin/pint --dirty --format agent` after all PHP file changes (none expected here, but check)
