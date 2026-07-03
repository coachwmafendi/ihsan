# Command Palette (Cmd+K) — Design Spec

## Goal

Add global command palette to app shell, English UI, matching provided mockup: search input, Esc badge, "PAGES" and "ACTIONS" sections, per-action keyboard hint badges.

## Scope (v1)

Static list only. No live DB search of donors/campaigns. Client-side substring filter over a fixed list of pages + actions.

## Trigger

- Cmd+K / Ctrl+K opens palette from anywhere inside the authenticated app shell.
- Topbar gets a "Search..." button styled like the mockup's search bar with a ⌘K badge; clicking it opens the palette too.
- Esc closes the palette.

## Component

- New Livewire component: `App\Livewire\App\CommandPalette`
  - View: `resources/views/livewire/app/command-palette.blade.php`
  - Mounted once inside `resources/views/components/app-shell.blade.php`, always present in DOM, hidden until opened.
- Uses `flux:modal` (same pattern as `resources/views/livewire/app/supporters/index.blade.php:369`).
- Filtering and keyboard navigation (arrow up/down, enter, highlight) run client-side via Alpine over a JS array rendered once by Livewire — no per-keystroke network round-trip.

## Data (static, English labels)

**PAGES**
| Label | Route |
|---|---|
| Dashboard | `app.dashboard` |
| UI Registry | `app.elements.index` |
| Donations | `app.donations.index` |
| Campaigns | `app.campaigns.index` |

**ACTIONS**
| Label | Hotkey | Behavior |
|---|---|---|
| New donation record | D | Navigate to `app.donations.index` (no create modal exists for donations yet; just links to the list) |
| Create campaign | K | Navigate to `app.campaigns.index?create=1` |

## Filtering & Keyboard Behavior

- Typing in the search input filters both PAGES and ACTIONS sections by case-insensitive substring match against label text.
- Arrow keys move the highlight across the combined filtered list (PAGES then ACTIONS, top to bottom). Enter activates the highlighted item.
- The `D` / `K` hotkey badges are cosmetic direct-trigger hints that only fire **while the search input is empty** (no query typed yet). Once the user has typed any character, `D`/`K` are treated as normal filter input, not shortcuts — avoids hijacking typed search queries.
- These hotkeys only work while the palette is open. No global (palette-closed) key bindings in v1.

## Create Campaign auto-open

No query-param support currently exists on `CampaignCreateModal` (it only opens via `#[On('open-create-campaign-modal')]`, normally triggered by `CampaignIndex::openCreateModal()` at `app/Livewire/App/Campaigns/CampaignIndex.php:119-122`).

Minimal scoped addition: in `CampaignIndex::mount()`, check `request()->boolean('create')`. If true, dispatch the `open-create-campaign-modal` browser event so the existing `CampaignCreateModal` listener opens it — reuses the existing modal/event wiring, no new modal component needed.

## Navigation

- Selecting a Page item navigates via `wire:navigate` to its route.
- Selecting the "Create campaign" action redirects to `app.campaigns.index` with `?create=1`.
- Selecting "New donation record" redirects to `app.donations.index` (plain link, no param).

## Out of scope (v1)

- Live search against donors/campaigns database records.
- Global (palette-closed) hotkeys.
- Standalone donation creation modal (doesn't exist yet — tracked separately).

## Testing

- New feature test for `CommandPalette` Livewire component: renders, filters list by search term (case-insensitive substring), palette open/close toggling.
- Addition to existing `CampaignIndexTest` (or equivalent): `?create=1` query param causes `open-create-campaign-modal` event dispatch / modal to be shown on mount.
