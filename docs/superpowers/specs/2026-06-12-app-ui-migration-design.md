# Design: Incremental Migration — `/app` from Filament to Custom Livewire UI

**Date:** 2026-06-12  
**Status:** Approved (2026-06-12)  
**Scope:** Customer-facing `/app` panel (NGO dashboard). Admin `/admin` panel stays on Filament.  
**Theme:** Light-mode SaaS premium (Stripe · Donorbox · Fru aesthetic)

---

## 1. Problem Statement

The `/app` panel is currently built on Filament (`app/Filament/App/`). Filament is optimised for admin CRUD and produces a generic dashboard aesthetic. The result:

- **UI feels admin-generic**, not customer-facing SaaS.
- **Heavy abstraction** fights custom UX flows (Virtual Terminal, Insights, Stripe Connect).
- **Overrides pile up** in `resources/views/vendor/filament-panels/` and `resources/views/filament/app/`.
- **Performance overhead** from Filament's JS/CSS even for simple pages.
- **Warm editorial intent** (nonprofit trustworthiness) is lost in the admin-panel chrome.

A custom UI built with Livewire full-page components + Tailwind will look and feel like Stripe, Donorbox, or Fru — clean, trustworthy, light, data-rich.

---

## 2. Design Goals

| Goal | How measured |
|------|------------|
| Eliminate Filament dependency in `/app` | `app/Filament/App/` deleted; zero vendor overrides for app panel |
| Achieve Stripe-like SaaS aesthetic | Visual audit against donation-ui-prototype reference |
| Maintain all existing features | Zero functional regression; 213+ tests still pass |
| Incremental deployability | Each phase deployable independently |
| Keep backend untouched | Models, Actions, Jobs, Webhooks, Mail remain unchanged |
| Responsive | Works on mobile, tablet, desktop |
| Accessible | WCAG 2.1 AA contrast, keyboard nav, semantic HTML |

---

## 3. High-Level Architecture

```
┌──────────────────────────────────────────────┐
│  User visits /app/dashboard                   │
│  → Route::get('/app/dashboard', Dashboard)   │
│  → Livewire full-page component               │
│  → #[Layout('layouts.app')] blade layout      │
│  → Renders inside app-shell (sidebar+topbar) │
└──────────────────────────────────────────────┘
```

Two panels coexist during and after migration:

| Panel | Technology | URL Prefix | Audience |
|-------|-----------|------------|----------|
| `/app` | Livewire 4 + Custom Tailwind | `/app/*` | NGO admins (customer-facing) |
| `/admin` | Filament v5 | `/admin/*` | Platform admins |

---

## 4. Design System

### 4.1 Color Palette (Light Mode)

Borrowed from `donation-ui-prototype` and refined.

| Token | Hex / Tailwind | Usage |
|-------|----------------|-------|
| `bg-page` | `#f7f7fb` | Page background |
| `bg-card` | `#ffffff` | Card surfaces |
| `border-default` | `#e2e8f0` (`slate-200`) | Card borders, dividers |
| `border-subtle` | `#f1f5f9` (`slate-100`) | Section separators |
| `text-primary` | `#0f172a` (`slate-900`) | Headings, primary text |
| `text-secondary` | `#64748b` (`slate-500`) | Descriptions, labels |
| `text-muted` | `#94a3b8` (`slate-400`) | Placeholders, timestamps |
| `accent` | `#0d9488` (`teal-600`) | Primary buttons, active nav, links |
| `accent-hover` | `#0f766e` (`teal-700`) | Button hover |
| `success` | `#10b981` (`emerald-500`) | Positive trends, paid status |
| `danger` | `#ef4444` (`red-500`) | Errors, refunds, failed |
| `warning` | `#f59e0b` (`amber-500`) | Pending, attention needed |

No dark mode scope for `/app` in this design. Dark mode stays for public embed/forms if needed, but `/app` is strictly light.

### 4.2 Typography

| Role | Size | Weight | Tracking | Color |
|------|------|--------|----------|-------|
| Page Title | `text-3xl` | `font-bold` | `tracking-tight` | `text-primary` |
| Section Title | `text-lg` | `font-semibold` | normal | `text-primary` |
| Card Title | `text-base` | `font-semibold` | normal | `text-primary` |
| Body | `text-sm` | `font-normal` | normal | `text-primary` |
| Caption / Muted | `text-sm` | `font-normal` | normal | `text-secondary` |
| Micro | `text-xs` | `font-medium` | normal | `text-muted` |

Font stack: `'Instrument Sans', ui-sans-serif, system-ui, sans-serif` (loaded via Bunny Fonts in Vite).

### 4.3 Spacing & Shape

| Token | Value | Usage |
|-------|-------|-------|
| Card radius | `rounded-xl` (12px) | All cards, panels |
| Button radius | `rounded-lg` (8px) | Buttons, inputs |
| Page padding | `p-6 md:p-8` | Main content area |
| Card padding | `p-5` or `p-6` | Inside cards |
| Grid gap | `gap-4` / `gap-6` / `gap-8` | Between cards, sections |
| Shadow | `shadow-none` or `shadow-sm` on rare overlays | Cards use **border**, not shadow |

### 4.4 Border-Driven UI

Key aesthetic rule from Stripe/Donorbox: **cards are defined by subtle borders, not drop shadows**. This creates a flatter, more editorial feel.

```
✅ rounded-xl border border-slate-200 bg-white
❌ rounded-lg shadow-lg bg-white
```

---

## 5. Component Architecture

Three layers of components.

### 5.1 Layout Layer (App Chrome)

```
layouts/app.blade.php        → root HTML shell, fonts, Livewire styles
components/app-shell.blade.php → sidebar + topbar + main content layout
components/sidebar.blade.php   → fixed nav, collapsible on mobile
components/topbar.blade.php    → org switcher, search, user menu
components/sidebar-group.blade.php
components/sidebar-item.blade.php
```

**Sidebar behavior:**
- Desktop: fixed left, 256px width, always visible
- Mobile: hidden by default, toggle via hamburger, slides in with overlay (Alpine.js)
- Active item: `bg-slate-100 text-slate-900 border-r-2 border-teal-600`
- Inactive item: `text-slate-500 hover:bg-slate-50 hover:text-slate-900`

**Sidebar navigation groups (optimized for NGO workflow):**

```
Fundraise
├── Dashboard
├── Campaigns
├── Elements
└── Insights

Finance
├── Donations
├── Recurring Plans
├── Payouts
└── Billing

Supporters
├── Donors
└── Subscriptions

Organization
├── Members
├── Teams
└── Settings
    ├── Profile
    ├── Payment
    ├── Notifications
├── Virtual Terminal
└── API & Developer
    ├── API Keys
    ├── Webhooks
    └── Embed Forms
```

**Topbar:**
- Left: mobile menu toggle + breadcrumb / page title
- Right: notification bell, user avatar dropdown
- **No global search / command palette**

### 5.2 UI Primitive Layer (Design System)

Reusable components in `components/ui/`. These are **pure Blade** (no Livewire state).

```
components/ui/card.blade.php         → title, description, actions, content, footer slots
components/ui/button.blade.php       → variants: primary, secondary, outline, ghost, danger; sizes: sm, md, lg
components/ui/input.blade.php        → label, error, helper text grouping
components/ui/select.blade.php       → label, error, helper text grouping
components/ui/table.blade.php        → table wrapper with header, body, row slots
components/ui/table-row.blade.php    → hover state, border-b
components/ui/badge.blade.php        → status colors: success, danger, warning, info, default
components/ui/empty-state.blade.php  → illustration slot, title, description, action
components/ui/toast.blade.php        → Alpine.js toast stack (success, error, info)
components/ui/modal.blade.php        → Alpine.js modal overlay + panel
components/ui/tabs.blade.php         → tab list + tab panels (Alpine)
components/ui/pagination.blade.php   → simple pagination links
components/ui/sparkline.blade.php    → SVG sparkline (ported from prototype)
components/ui/area-chart.blade.php   → SVG area chart with gradient
components/ui/doughnut.blade.php     → SVG doughnut chart
components/ui/horizontal-bar.blade.php → SVG horizontal bar chart
```

Every `ui/*` component uses only Tailwind classes + Alpine.js for interactions. No Flux, no Filament.

### 5.3 Page Layer (Livewire Full-Page Components)

Each page is a **Livewire class-based full-page component** (`php artisan make:livewire App/Dashboard --class`).

Rationale for class-based (not single-file/SFC):
- Pages require substantial PHP logic (queries, validation, authorization).
- Routing with `Route::get('/', ClassName::class)` is explicit and standard.
- Matches existing project convention (`app/Livewire/DonationForm.php`).

```
app/Livewire/App/
├── Dashboard.php
├── Campaigns/
│   ├── CampaignIndex.php
│   ├── CampaignCreate.php
│   └── CampaignShow.php
├── Donations/
│   ├── DonationIndex.php
│   └── DonationShow.php
├── Donors/
│   ├── DonorIndex.php
│   └── DonorShow.php
├── Subscriptions/
│   └── SubscriptionIndex.php
├── Elements/
│   ├── ElementIndex.php
│   ├── ElementCreate.php
│   └── ElementEdit.php
├── Settings/
│   ├── SettingsProfile.php
│   ├── SettingsPayment.php
│   └── SettingsNotifications.php
├── Insights.php
├── Billing.php
└── VirtualTerminal.php

resources/views/livewire/app/
├── dashboard.blade.php
├── campaigns/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── show.blade.php
├── donations/
│   ├── index.blade.php
│   └── show.blade.php
├── donors/
│   ├── index.blade.php
│   └── show.blade.php
├── subscriptions/
│   └── index.blade.php
├── elements/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── settings/
│   ├── profile.blade.php
│   ├── payment.blade.php
│   └── notifications.blade.php
├── insights.blade.php
├── billing.blade.php
└── virtual-terminal.blade.php
```

Inside each page:
- PHP block: state (`public $search`, `$sort`, etc.), actions (`save()`, `delete()`), lifecycle hooks (`mount()`)
- Blade block: use `ui/*` components, inline Tailwind, Alpine.js for local interactivity (dropdowns, modals)

---

## 6. Page Migration Plan

Migrate in **5 phases**. Each phase is independently testable and deployable.

### Phase 1 — Foundation (Week 1)

**Goal:** Design system + layout shell + pilot page (Dashboard).

| Task | Files to create |
|------|-----------------|
| Define Tailwind tokens in `resources/css/app.css` | `@theme` block |
| Build `components/app-shell.blade.php` | Layout wrapper |
| Build `components/sidebar.blade.php` | Navigation |
| Build `components/topbar.blade.php` | Top navigation |
| Build `components/ui/card.blade.php` | Base card |
| Build `components/ui/button.blade.php` | Buttons |
| Build `components/ui/badge.blade.php` | Status badges |
| Build `components/ui/empty-state.blade.php` | Empty state |
| Port sparkline + area chart from prototype | `components/ui/sparkline.blade.php`, `area-chart.blade.php` |
| Create `app/Livewire/App/Dashboard.php` + `resources/views/livewire/app/dashboard.blade.php` | Full-page Dashboard with stats + charts (class-based component) |
| Update `/app` route to point to Dashboard | `routes/web.php` |

**Kriteria selesai:**
- `/app/dashboard` renders fully in new UI.
- Sidebar navigates to existing Filament pages for unmigrated routes (temporary hybrid state).
- Mobile responsive verified.

### Phase 2 — Settings & Static Pages (Week 1–2)

**Goal:** Migrate pages that are mostly forms and static content.

| Page | Complexity | Notes |
|------|-----------|-------|
| Settings / Org Profile | Low | Form dengan state/country select |
| Settings / Payment | Low | Stripe Connect read-only status + onboarding CTA |
| Settings / Notifications | Low | Toggle cards, auto-save |
| Billing | Medium | Invoice list + billing stats |
| Virtual Terminal | Medium | Form + preloaded link display |
| Stripe Onboarding | Low | Redirect / status page |

**Kriteria selesai:**
- All Settings group pages rendered in new UI.
- Form validation works via Livewire `validate()`.
- Auto-save toggles work (use `wire:model.live` + `updated()`).

### Phase 3 — Data Tables (Week 2–3)

**Goal:** Migrate list/index pages with search, filter, sort, pagination.

| Page | Complexity | Notes |
|------|-----------|-------|
| Campaigns / Index | Medium | Search, status filter, sort, pagination |
| Campaigns / Create | Medium | Form campaign |
| Campaigns / Edit | Medium | Form + inline relation sections (Donations list, Elements list, Subscriptions list rendered as separate queries/components below the form) |
| Donations / Index | Medium-Hard | Period filter (rebuild Insights-style dropdown), search, status filter |
| Donors / Index | Medium | Search, filter, pagination |
| Subscriptions / Index | Medium | Table + status filters |
| Elements / Index | Medium | Search, type filter |

**Table pattern:**
- Search bar above table (debounced `wire:model.live.debounce.300ms`)
- Filter buttons/dropdowns (Alpine.js) — NOT Filament filter UI
- Sortable headers (`wire:click="sortBy('column')"`)
- Row actions: View, Edit links (kekal simple)
- Pagination via `ui/pagination` or Laravel paginator rendered custom

**Kriteria selesai:**
- All CRUD list pages functional.
- Period filter rebuild matching prototype/Insights UX.
- No Filament table artifacts remain in `/app`.

### Phase 4 — Detail & Embedded Pages (Week 3)

**Goal:** Show/detail pages and embed flows.

| Page | Complexity | Notes |
|------|-----------|-------|
| Campaigns / Show | Medium | Stats + donations list + elements list |
| Donations / Show | Medium | Donation detail view, card info, receipt download |
| Donors / Show | Medium-Hard | Donor info + donations relation + subscriptions relation |
| Subscriptions / Show | Medium | Subscription detail + receipts |
| Elements / Create & Edit | Medium-Hard | Element form (Popup redesign from previous work) + embed code |
| Embeds / Public form | Medium | Kept as is (existing `Livewire/DonationForm.php` works) |

**Kriteria selesai:**
- All detail pages reachable from list pages.
- Embed code generation still works (`data-type` attribute on `/e/widget.js`).
- PDF receipt generation unchanged.

### Phase 5 — Insights & Cleanup (Week 3–4)

**Goal:** Rebuild Insights page + remove Filament App panel entirely.

| Task | Notes |
|------|-------|
| Rebuild Insights page | Reference `donation-ui-prototype` dashboard charts; port tab layout |
| Delete `app/Filament/App/` | Entire directory |
| Delete `resources/views/filament/app/` | Entire directory |
| Delete `resources/views/vendor/filament-panels/` entries for app | If any app-specific overrides |
| Update routes | Ensure noFilament routes for `/app` |
| Final test run | `php artisan test --compact` — all 213+ tests pass |
| Visual QA | Stripe/Donorbox feel check |

**Kriteria selesai:**
- `/app` is 100% custom Livewire UI.
- Zero references to `Filament\App` namespace.
- `/admin` still works on Filament.
- All tests green.

---

## 7. Routing Strategy

During migration, routes may temporarily coexist. The final target:

```php
// routes/web.php

use App\Livewire\App\Dashboard;
use App\Livewire\App\Campaigns\CampaignIndex;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Livewire\App\Campaigns\CampaignShow;
// ... etc

Route::middleware(['auth', 'verified', 'org-context'])->prefix('app')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('app.dashboard');

    Route::get('/campaigns', CampaignIndex::class)->name('app.campaigns.index');
    Route::get('/campaigns/create', CampaignCreate::class)->name('app.campaigns.create');
    Route::get('/campaigns/{campaign}', CampaignShow::class)->name('app.campaigns.show');

    Route::get('/donations', DonationIndex::class)->name('app.donations.index');
    Route::get('/donations/{donation}', DonationShow::class)->name('app.donations.show');

    Route::get('/donors', DonorIndex::class)->name('app.donors.index');
    Route::get('/donors/{donor}', DonorShow::class)->name('app.donors.show');

    Route::get('/subscriptions', SubscriptionIndex::class)->name('app.subscriptions.index');
    Route::get('/subscriptions/{subscription}', SubscriptionShow::class)->name('app.subscriptions.show');

    Route::get('/elements', ElementIndex::class)->name('app.elements.index');
    Route::get('/elements/create', ElementCreate::class)->name('app.elements.create');
    Route::get('/elements/{element}/edit', ElementEdit::class)->name('app.elements.edit');

    Route::get('/insights', Insights::class)->name('app.insights');
    Route::get('/billing', Billing::class)->name('app.billing');
    Route::get('/virtual-terminal', VirtualTerminal::class)->name('app.virtual-terminal');
    Route::get('/stripe-onboarding', StripeOnboarding::class)->name('app.stripe-onboarding');

    Route::get('/settings/profile', SettingsProfile::class)->name('app.settings.profile');
    Route::get('/settings/payment', SettingsPayment::class)->name('app.settings.payment');
    Route::get('/settings/notifications', SettingsNotifications::class)->name('app.settings.notifications');
});
```

**Auth middleware:** Reuse existing `auth` + Fortify verified + any custom `EnsureOrganization` middleware. No change.

---

## 8. Data-Fetching Patterns

### 8.1 Tables with Search & Filter

```php
class DonationIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $period = '30_days'; // all_time, today, yesterday, 7_days, 30_days, 90_days, this_month
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function donations(): LengthAwarePaginator
    {
        return Donation::query()
            ->when($this->search, fn ($q) => $q->where('donor_name', 'like', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->period !== 'all_time', fn ($q) => $q->whereBetween('created_at', $this->periodRange()))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(25);
    }
}
```

### 8.2 Forms with Validation

```php
class CampaignCreate extends Component
{
    public string $name = '';
    public string $description = '';
    public ?string $goalAmount = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'goalAmount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();
        Campaign::create([
            ...$validated,
            'organization_id' => auth()->user()->organization_id,
        ]);
        $this->redirectRoute('app.campaigns.index');
    }
}
```

### 8.3 Settings Auto-Save

```php
class SettingsNotifications extends Component
{
    public bool $notifyNewDonation = true;
    public bool $dailySummary = false;
    public ?string $dailySummaryTime = '08:00';

    public function mount(): void
    {
        $settings = auth()->user()->organization->settings ?? [];
        $this->notifyNewDonation = $settings['notify_new_donation'] ?? true;
        $this->dailySummary = $settings['daily_donation_summary'] ?? false;
        $this->dailySummaryTime = $settings['daily_summary_time'] ?? '08:00';
    }

    public function updated($property): void
    {
        // Auto-save on change
        $this->save();
    }

    public function save(): void
    {
        auth()->user()->organization->update([
            'settings' => array_merge(
                auth()->user()->organization->settings ?? [],
                [
                    'notify_new_donation' => $this->notifyNewDonation,
                    'daily_donation_summary' => $this->dailySummary,
                    'daily_summary_time' => $this->dailySummaryTime,
                ]
            ),
        ]);
    }
}
```

---

## 9. Backend Compatibility

**Strict rule:** Do not modify models, actions, jobs, mail, webhooks, or services unless absolutely required for the migration.

The only permissible changes:
- Add `route()` helpers or `#[Computed]` properties in Livewire components
- Add `with()` eager-load relationships in component queries to avoid N+1
- Add read-only accessors (getXAttribute) on models for display formatting only — no schema changes

**Relation Managers:** Filament relation managers do not exist in the new UI. Instead, relation data is fetched via Eloquent queries inside the parent page component and rendered as inline table components or child Livewire components (e.g., a Campaign Show page queries its donations and passes them to a `ui/table`).

All existing tests must remain untouched and pass.

---

## 10. Asset Pipeline

### 10.1 CSS

`resources/css/app.css` — add design tokens to existing Tailwind v4 setup:

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css'; /* keep for existing Flux usage if any */

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';

@theme {
  --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';

  /* Page backgrounds */
  --color-page: #f7f7fb;
  --color-page-dark: #0f172a;

  /* Accent */
  --color-accent: #0d9488;
  --color-accent-hover: #0f766e;
  --color-accent-subtle: #f0fdfa;

  /* Status */
  --color-success: #10b981;
  --color-danger: #ef4444;
  --color-warning: #f59e0b;
}
```

### 10.2 JS

`resources/js/app.js` — minimal. Alpine.js is already bundled with Livewire 4. Add only global utilities if needed (e.g., clipboard copy helper).

### 10.3 Fonts

Keep existing Vite Bunny Fonts setup for Instrument Sans. No new font dependencies.

---

## 11. Chart Strategy

Use **custom SVG components** ported from `donation-ui-prototype`. Rationale:

| Approach | Pros | Cons |
|----------|------|------|
| Custom SVG (chosen) | Zero dependency, matches design system exactly, lightweight, supports interactivity via Alpine | Manual math for paths |
| Chart.js / ApexCharts | Feature-rich | Heavy bundle, generic look, harder to style to Stripe aesthetic |
| Flux charts (if any) | Integrated | Limited, may not fit custom aesthetic |

Charts to port:
- Sparkline (mini line for stats cards)
- Area chart with gradient fill (donation trends)
- Doughnut chart (campaign splits)
- Horizontal bar chart (top campaigns)
- Range bar (donation sizes)

---

## 12. Mobile Responsiveness

| Breakpoint | Behavior |
|------------|----------|
| `< 1024px` (mobile/tablet) | Sidebar hidden, hamburger toggle, drawer overlay. Content full width. Stats grid 1–2 cols. |
| `>= 1024px` (desktop) | Sidebar fixed visible. Content grid 8+4 or 12 cols. |

Key mobile UX rules:
- Touch targets min 44px
- Table rows tappable → navigate to detail
- Filters collapse into single dropdown button
- Horizontal scroll for wide tables with fade indicators

---

## 13. Testing Strategy

| Type | Tool | What to test |
|------|------|-------------|
| Unit | Pest v4 | Public methods on Livewire components (mount, actions, validation rules) |
| Feature | Pest v4 | Route access, page render, form submission, search/filter behavior, auth gates |
| Browser | Laravel Dusk / Playwright (future) | End-to-end flows: create campaign → view dashboard |
| Visual | Manual | Aesthetic check against Stripe/Donorbox on every phase |

**Test requirement:**
- For every migrated page, write or update a feature test that asserts the page renders successfully.
- Assert that settings auto-save updates database correctly.
- Assert that table search/filter/sort returns correct results.
- Run `php artisan test --compact` before declaring any phase complete.

---

## 14. Rollback Plan

Each phase is deployed independently. If a phase introduces issues:

1. The previous phase's code remains in Git history.
2. Filament app panel routes can be temporarily restored by uncommenting relevant lines in Filament service provider.
3. Individual page routes can be reverted without affecting others.

**Recommended:** Tag Git releases after each phase (`phase-1-foundation`, `phase-2-settings`, etc.).

---

## 15. Open Questions — Answered

| # | Question | Answer |
|---|----------|--------|
| 1 | Sidebar navigation order — mirror Filament or optimize for NGO workflow? | **Optimize for NGO workflow.** Reorganized into 4 groups: Fundraise, Finance, Organization, Developer. |
| 2 | Insights rebuild timing — Phase 5 or earlier? | **Phase 5 is fine.** |
| 3 | Global command palette (Cmd+K)? | **No.** Sidebar + topbar search is sufficient. |
| 4 | Preserve `wire:navigate` SPA-like transitions? | **Yes.** Use `wire:navigate` on all internal `/app` links for smooth transitions. |

---

## 16. Decision Log

| Decision | Rationale |
|----------|-----------|
| Light mode only for `/app` | User explicitly wants Stripe/Donorbox/Fru aesthetic; all three are light-first |
| Custom Tailwind, no Flux/Filament for app chrome | Flux components feel generic; full control needed for premium SaaS feel |
| Livewire class-based full-page components | Matches existing project convention (`app/Livewire/DonationForm.php`); explicit routing; easier to organize substantial PHP logic |
| DonationForm (public embed) stays untouched | It works, it's independent, not part of `/app` panel UX |
| SVG charts, not library | Design alignment, zero dependency, lightweight |
| Incremental 5-phase migration | Risk mitigation, deployable milestones, test coverage gradually moves |
| Sidebar reorganized for NGO workflow (not mirroring Filament) | User wants workflow-optimized navigation: Fundraise → Finance → Supporters → Organization → Developer |
| No command palette | User prefers sidebar simplicity |
| Preserve `wire:navigate` | User wants SPA-like transitions between pages |
