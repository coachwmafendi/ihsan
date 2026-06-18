# Tooltip Standardization Design

## Goal
Replace every application-controlled tooltip in the codebase with a single, instant, premium, keyboard-accessible tooltip component that never overflows the viewport, while keeping Filament's internal Tippy-based tooltips on the same timing settings.

## Principles
- One reusable component, no duplicated tooltip markup.
- 75 ms hover delay; tooltips feel instant.
- No sluggish fade/scale animations; only a 75 ms opacity/scale transition.
- Keyboard accessible: focus shows, Escape hides, auto-focusable when the trigger isn't already interactive.
- Viewport safe: top-level fixed positioning with runtime overflow detection and flipping.
- Styling standardized: dark zinc shell used in both app and admin contexts.

## Existing Audit
Three tooltip systems currently coexist:
1. **Native `title="..."`** on icons, badges, spans, and info text across app blades and Filament form components.
2. **`<flux:tooltip>`** in:
   - `resources/views/livewire/app/subscriptions/index.blade.php`
   - `resources/views/flux/sidebar/item.blade.php`
3. **Filament `x-tooltip`** in:
   - `resources/views/vendor/filament-panels/components/theme-switcher/button.blade.php`
   - `resources/views/vendor/filament-panels/components/tenant-menu.blade.php`
   - `resources/views/vendor/filament-panels/components/sidebar/item.blade.php`
   - `resources/views/vendor/filament-panels/components/sidebar/group.blade.php`
   - plus many internal Filament vendor components outside our control.

We will replace the first two plus the application-controlled `x-tooltip` overrides. Internal Filament components will stay on Tippy but inherit the same 75 ms delay and shorter duration via a global default-props override.

## Component API

### File
`resources/views/components/ui/tooltip.blade.php`

### Props
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `text` | `?string` | `null` | Plain-text tooltip content. |
| `position` | `string` | `'top'` | Preferred placement: `top`, `bottom`, `left`, `right`. |
| `align` | `string` | `'center'` | Cross-axis alignment: `start`, `center`, `end`. |
| `delay` | `int` | `75` | Show delay in milliseconds. |
| `maxWidth` | `string` | `'max-w-xs'` | Tailwind max-width class. |
| `disabled` | `bool` | `false` | If `true`, the tooltip never opens. |

### Slot
- **Default slot**: the trigger element(s).
- **`tip` slot** (optional): rendered as the tooltip body, useful for line breaks or basic inline markup. Must not contain interactive content.

### Examples
```blade
<x-ui.tooltip text="Recurring donation">
    <x-heroicon-o-arrow-path class="size-4 text-teal-500" />
</x-ui.tooltip>

<x-ui.tooltip position="right" align="start">
    <x-heroicon-o-question-mark-circle class="size-4 text-slate-400" />
    <x-slot:tip>
        Platform <strong>fee</strong> charged by Ihsan.
    </x-slot:tip>
</x-ui.tooltip>
```

## Behavior

### Show / hide
- `mouseenter` → schedule show after `delay` (default 75 ms).
- `mouseleave` → cancel timer, hide immediately.
- `focusin` → show immediately (no delay) for keyboard users.
- `focusout` → hide immediately.
- `Escape` key window listener hides any open tooltip.
- `disabled` prop prevents all open paths.

### Positioning and viewport safety
- Tooltip is teleported to `document.body` and positioned with `position: fixed`.
- On open, the trigger and tooltip bounding rects are measured against `window.innerWidth/Height`.
- If the tooltip overflows the viewport on the preferred side, it flips:
  - `top` ↔ `bottom`
  - `left` ↔ `right`
- If it still overflows horizontally/vertically after flipping, it is edge-aligned to keep the full box visible.
- On window resize or scroll, the tooltip hides to avoid stale coordinates.

### Keyboard accessibility
- The component detects server-side whether the default slot starts with an interactive element (`button`, `a`, `input`, `select`, `textarea`). If it does not, the trigger wrapper is rendered with `tabindex="0"`, `role="img"`, and an `aria-label` derived from the tooltip text.
- At runtime the component also finds the actual focusable target (child or wrapper) so `aria-describedby` points to it while the tooltip is open.
- The tooltip itself has `role="tooltip"` and an ID shared with `aria-describedby`.
- `Escape` closes any open tooltip.

### Animation
- `transition ease-out duration-75` on enter.
- `transition ease-in duration-75` on leave.
- Transform: `opacity` and a tiny `scale` (0.95 → 1).

### Visual style
```
bg-slate-800 text-white text-xs font-medium
rounded-md px-2.5 py-1.5 shadow-lg z-50
max-w-xs whitespace-normal
```
For dark mode contexts (`dark` class), keep `bg-slate-800` unless the surrounding theme requires a different shade; the chosen dark zinc works in both.

## JS Resource

### File
`resources/js/tooltip.js`

### Responsibility
Export an Alpine data object factory used by the Blade component:
```js
document.addEventListener('alpine:init', () => {
    Alpine.data('uiTooltip', (config) => ({
        open: false,
        timer: null,
        tipId: 'tooltip-' + Math.random().toString(36).slice(2),
        trigger: null,
        focusTarget: null,
        positionStyle: {},
        // show, hide, init, reposition, etc.
    }));
});
```

### Registration
`resources/js/app.js` imports the module:
```js
import './tooltip.js';
```

## Filament Tippy Defaults
Add the following snippet at the bottom of `resources/views/vendor/filament-panels/components/layout/base.blade.php`, after `@filamentScripts`:
```html
<script data-navigate-once>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.tippy) {
            tippy.setDefaultProps({
                delay: [75, 0],
                duration: [100, 75],
                moveTransition: 'transform 0.1s ease-out',
            });
        }
    });
</script>
```
This aligns internal Filament tooltips with the 75 ms delay requirement without vendor-overriding every Filament component.

## Migration Scope

### Custom components
- `resources/views/components/ui/copy-button.blade.php`
- `resources/views/components/report-amount.blade.php`
- `resources/views/components/donation-report-amount.blade.php`

### App blades (native `title="..."`)
- `resources/views/livewire/app/donations/index.blade.php`
- `resources/views/livewire/app/donations/show.blade.php`
- `resources/views/livewire/app/supporters/index.blade.php`
- `resources/views/livewire/app/supporters/show.blade.php`
- `resources/views/livewire/app/subscriptions/index.blade.php`
- `resources/views/livewire/app/subscriptions/show.blade.php`
- `resources/views/livewire/app/insights.blade.php`
- `resources/views/livewire/app/dashboard.blade.php`
- `resources/views/livewire/app/audit-log/index.blade.php`
- `resources/views/livewire/app/elements/index.blade.php`
- `resources/views/livewire/app/campaigns/edit.blade.php`

### Filament form / table components
- `resources/views/filament/forms/components/element-embed-snippet.blade.php`
- `resources/views/filament/forms/components/donor-portal-link.blade.php`
- `resources/views/filament/forms/components/vt-preloaded-link.blade.php`
- `resources/views/filament/forms/components/virtual-terminal-link.blade.php`
- `resources/views/filament/forms/components/suggested-amounts.blade.php`
- `resources/views/filament/tables/columns/embed-code-column.blade.php`

### Flux tooltips
- `resources/views/livewire/app/subscriptions/index.blade.php` (currency info icon)
- `resources/views/flux/sidebar/item.blade.php`
- `resources/views/flux/sidebar/group.blade.php` if tooltip usage exists

### Filament panel overrides
- `resources/views/vendor/filament-panels/components/theme-switcher/button.blade.php`
- `resources/views/vendor/filament-panels/components/tenant-menu.blade.php`
- `resources/views/vendor/filament-panels/components/sidebar/item.blade.php`
- `resources/views/vendor/filament-panels/components/sidebar/group.blade.php`

### Not in scope
- Empty-state `title` props, card headers, page titles, iframe titles, layout title attributes.
- Internal Filament vendor components not overridden in `resources/views/vendor/filament-panels` (handled by Tippy defaults).

## Testing

### `tests/Feature/Components/TooltipTest.php`
- Render component with `text` prop and assert tooltip body contains the text.
- Render with `<x-slot:tip>` and assert unescaped HTML is rendered safely.
- Assert an icon trigger gains `tabindex="0"` and `role="img"`.
- Assert a button trigger is left focusable without a second `tabindex` and receives `aria-describedby` while open.
- Assert `disabled` prop prevents rendering/tooltip even on hover markup.

### Existing feature tests
- Run the relevant existing feature tests after migration to ensure no regressions in pages that had title attributes.

## Risks and Mitigations
| Risk | Mitigation |
|------|------------|
| Wrapping block-level triggers in `<span>` changes layout. | Use an inline wrapper and let the inner element define layout; wrap only the trigger element, not surrounding layout. |
| Dynamic tooltips in sidebar rely on `$store.sidebar.isOpen`. | Pass a reactive `disabled` prop bound to the store so tooltips are suppressed when the sidebar is expanded. |
| Tooltip `x-teleport` may conflict with Filament's CSP or Livewire `wire:navigate`. | Use `x-cloak`, keep IDs unique in Alpine, and rely on `data-navigate-once` for Tippy defaults. |
| Removing `title` loses the browser-native fallback. | Replaced by full keyboard/focus support plus `aria-describedby`, so no fallback is needed. |

## Open Decisions
None remaining after clarification with the user.
