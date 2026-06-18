# Tracking & Analytics Settings Redesign

**Date:** 2026-06-19  
**Scope:** App settings page at `/app/settings/tracking`

## Problem

The current Tracking & Analytics settings page stacks every provider as a full-width card. With 7 providers (and growing), the page becomes:

- Hard to scan (A),
- Crowded per card because credentials, toggles, actions, and hints share one surface (B),
- Slow to judge which providers are active (C),
- Buried further by the Event Diagnostics, Advanced Tracking, and Donation Attribution Preview sections (D).

## Goal

Reduce visual density and cognitive load while keeping all current functionality:

1. See every provider’s status at a glance.
2. Edit one provider at a time with room to breathe.
3. Keep global sections (Advanced Tracking, Event Diagnostics, Attribution Preview) clearly separated.
4. Work on mobile without losing context.

## Proposed Layout: Two-Pane + Global Sections (Option B)

### Top half: two-pane workspace

- **Left sidebar (≈260 px on desktop):** vertical list of all `TrackingProvider` cases.
  - Provider icon + label.
  - Status badge (Active, Configured, Not Configured, Error).
  - Active provider is highlighted.
  - Click/Tap selects the provider.
- **Right detail panel:** configuration for the selected provider.
  - Provider header (name, description, status badge).
  - Credential fields (1-column or 2-column depending on field count).
  - Toggle options (track page views, etc.).
  - Action row: Test Connection + Save Changes.

### Bottom half: full-width global cards

These sections apply to the whole organization, not a single provider:

1. **Advanced Tracking**
   - Attribution window radios (30/60/90 days).
   - Captured parameters toggles (UTM, click IDs, referrer, landing page).
   - Save button.
2. **Event Diagnostics**
   - Search input + provider filter.
   - Recent events table.
3. **Donation Attribution Preview**
   - Collapsible example showing captured attribution fields.

### Mobile behavior

- The provider list becomes the default view.
- Tapping a provider pushes/open the detail panel (slide-in or stacked).
- A back button returns to the list.
- Global sections remain below the list/detail area.

## State & Data Flow

- `App\Livewire\App\Settings\Tracking` remains the single component.
- Add a public property `$selectedProvider` (string, defaults to first configured provider or `'meta'`).
- `mount()` sets the initial selected provider.
- Sidebar selection calls `$set('selectedProvider', $slug)` or a method `selectProvider(string $slug)`.
- Save/Test/SaveAdvanced continue to target the currently selected provider.
- Computed property `selectedConfiguration()` returns the `TrackingConfiguration` for `$selectedProvider`.
- Keep existing `configurations()` and `events()` computed properties.

## Components / UI Details

### Provider list item

```blade
<button
    wire:click="selectProvider('{{ $slug }}')"
    class="... {{ $isActive ? 'active styles' : '' }}"
>
    @include('livewire.app.settings.tracking-provider-icon', ['provider' => $provider])
    <span>{{ $provider->label() }}</span>
    <x-ui.status-badge :status="$status->value" size="sm">{{ $status->label() }}</x-ui.status-badge>
</button>
```

### Empty / not-configured state in detail panel

If the selected provider has no credentials, show:

- Short description of what this provider tracks.
- Credential inputs.
- A secondary action linking to the provider’s docs (if available).

### Status badge mapping

Use existing `TrackingProviderStatus` enum and its badge/dot classes. No new status values needed.

## Interactions

| Action | Behavior |
|--------|----------|
| Click provider in sidebar | Load its config in the right panel. |
| Save Changes | Saves credentials + options for `$selectedProvider`; status badge updates. |
| Test Connection | Calls existing `testConnection($selectedProvider)`. |
| Save Advanced Settings | Calls existing `saveAdvanced()`. |
| Search/filter diagnostics | Existing live-debounce behavior kept. |
| Toggle Attribution Preview | Calls existing `toggleShowAttribution()`. |

## Accessibility

- Sidebar uses `<nav aria-label="Providers">` and buttons, not links.
- Active provider has `aria-current="true"`.
- Form inputs keep associated labels.
- Mobile panel has a visible back button and focus trap if implemented as a slide-over.

## Tests to Update

- `tests/Feature/App/TrackingTest.php`
  - Provider selection dispatches no error and renders selected form.
  - Saving provider updates the correct `TrackingConfiguration` record.
  - Test Connection still fires correct HTTP request.
  - Advanced settings save persists to `organization.settings`.

## Files To Edit

- `resources/views/livewire/app/settings/tracking.blade.php` — main view split.
- `app/Livewire/App/Settings/Tracking.php` — add `selectedProvider` state and selection method.
- `tests/Feature/App/TrackingTest.php` — adjust/add tests for new layout.

## Out of Scope

- Adding new providers.
- Changing credential field definitions in `TrackingProvider`.
- Changing event sending logic in `SendMetaConversionEvent` or other jobs.

## Open Questions

None at this stage; layout and behavior approved by product owner.
