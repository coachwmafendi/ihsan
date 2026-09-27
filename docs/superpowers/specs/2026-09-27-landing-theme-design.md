# Landing Page Theme and Feature Grid — Design

## Goal

Give `getihsan.my` a named colour palette and more presence in its feature grid,
without changing its identity. Teal stays; the dark stays.

## Scope

`resources/views/welcome.blade.php` and the `@theme` block in
`resources/css/landing.css`. Nothing else.

The case study page, the demo page and the new policy pages share the landing
layout but keep exactly the look they have now. The NGO panel (`app.css`), the
donation checkout (`checkout.css`), the Filament admin theme and the donor
portal load different stylesheets and are untouched.

## What was found, and what turned out not to be true

The landing uses three hand-picked hex values with no names:

| Value | Role | Uses |
| --- | --- | --- |
| `#0f172a` | Page canvas and chrome: nav, mobile menu, sections, mock sidebar | 4 |
| `#111827` | Surface inside the product mockups only | 3 |
| `#131d31` | Alternating full-width band: features, pricing | 2 |

Teal appears in four shades, each by the weight of what it marks: `teal-600`
solid buttons (24), `teal-500` hover and gradient (15), `teal-400` accent text
and icons (18), `teal-300` large numerals (5).

**A claim made earlier in this work was wrong and is recorded here so it is not
repeated.** Three SVGs carry `stroke-width="2"` while the rest carry `1.5`, and
that looked like drift. It is not: one is a chart line inside a mockup, not an
icon, and the other two are checkmarks at 20px, where the heavier stroke is what
makes a tick read at that size. A full inventory of all 22 SVGs shows one
viewBox, coherent sizes, and no inconsistency to fix. The icon work in this
design is therefore a deliberate visual change, not a correction.

## Changes

### Colour tokens

Name the three values in `landing.css`, so the next person picks by role rather
than by eye:

```css
@theme {
    --color-canvas: #0f172a;
    --color-mockup: #111827;
    --color-band: #16203a;
}
```

Tailwind v4 generates `bg-canvas`, `bg-canvas/80`, `bg-mockup`, `bg-band` from
these. Every `bg-[#...]` in `welcome.blade.php` becomes a named utility.

`--color-band` is the one value that changes: `#131d31` to `#16203a`. The two
band sections sit so close to the canvas today that the page reads as one
continuous panel. The wider gap, plus a teal hairline along the top of each
band, gives scrolling a sense of moving between sections.

Teal keeps Tailwind's own scale. Naming `teal-600` as `--color-accent` would put
a layer between the reader and the value without telling them anything the
number does not.

### Feature grid

Six cards in the `#features` section:

| | Now | After |
| --- | --- | --- |
| Icon tile | 40px, radius 8, `teal-600/20` | 48px, radius 12, `teal-500/15` with an inset `teal-400/28` ring |
| Icon | 20px, `teal-400` | 24px, `teal-300` |
| Card hover | border to `teal-500/30` | border to `teal-400/45`, lifts 3px, drop shadow |

The lift uses `transform`, which does not affect layout, and is expressed with
Tailwind's `hover:` variant, so it never fires on touch.

## Testing

Appearance is judged by eye, in a browser, before and after. Three guards keep
what was decided from drifting back:

- `welcome.blade.php` contains no `bg-[#` arbitrary colour.
- The feature icon tiles all use the same size class, so one card cannot be
  edited out of step with the other five.
- The landing still returns 200 and still renders the shared footer — already
  covered by `tests/Feature/LegalPagesTest.php`.

## Risks

`welcome.blade.php` is 540 lines of markup and the edits are spread across it.
The guards above catch a missed hex or a mismatched tile, but not a broken
layout, so both the features and pricing sections are checked visually before
and after.
