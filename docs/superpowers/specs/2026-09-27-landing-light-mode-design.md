# Landing Page Light Mode — Design

## Goal

Give `getihsan.my` a light mode and a toggle, with dark kept as it is today.

## Why this is a rewrite rather than an addition

The landing has **zero** `dark:` classes. It is not a dark theme; it is dark
colours typed directly into 540 lines of markup. Adding light mode means
writing the light value as the base and moving every current value behind the
`dark:` variant — across the nav, hero, two product mockups, features, the
NGO/donor columns, pricing, FAQ and footer.

What already exists and does not need building:

- `landing.css` declares `@custom-variant dark (&:where(.dark, .dark *))`, so a
  class-based dark variant is wired up and unused.
- The NGO panel already uses `dark:` classes, so the pattern is established.
- `resources/views/email-logs/responsive-preview.blade.php` has a working
  toggle: Alpine, seeded from `prefers-color-scheme`, sun and moon icons.

## Decisions

| Question | Decision |
| --- | --- |
| Which is the base | Light. Dark moves behind `dark:`, matching the NGO panel and Tailwind's own convention. |
| First visit | Follows the visitor's system setting. |
| Toggle | A sun/moon button on the landing, remembered in `localStorage`. |
| Scope | `welcome.blade.php`. The case study, demo and policy pages keep the single look they have. |
| Product mockups | Follow the toggle. In light mode they go light, which also makes them resemble the real NGO panel for the first time — that panel is light today while the mockup portrays a dark one. |

## Light palette

| Surface | Light | Dark (today) |
| --- | --- | --- |
| Canvas | `white` | `canvas` `#0f172a` |
| Section band | `teal-50` | `band` `#16203a` |
| Mockup surface | `slate-50` | `mockup` `#111827` |
| Card | `white`, `slate-200` border | `slate-800/50`, `slate-700/50` border |
| Heading | `slate-900` | `white` |
| Body copy | `slate-600` | `slate-400` |
| Accent text | `teal-600` | `teal-400` |
| Large numerals | `teal-700` | `teal-300` |
| Primary button | `teal-600` | `teal-600` — the one value shared by both |

## The one file outside the stated scope

The `dark` class must sit on `<html>` for the variant to match, and the script
that sets it must run before first paint or the page visibly flashes the wrong
theme. That means `<head>`, which lives in `layouts/landing.blade.php`.

Four lines go there. They read `localStorage` and a media query and set one
class. Pages with no `dark:` classes are unaffected by the class being present,
so the case study, demo and policy pages keep behaving exactly as they do now.

## Toggle behaviour

- No stored choice: follow `prefers-color-scheme`.
- Button flips the class on `<html>` and writes the choice to `localStorage`.
- A stored choice wins over the system setting on later visits.

## Testing

Appearance is judged in a browser, in both modes. The guards are:

- `welcome.blade.php` still contains no bare `bg-[#` or `text-[#`.
- Every `dark:` background class in `welcome.blade.php` has a light class on the
  same element, so no element can be styled for one mode only.
- The landing returns 200 and renders the shared footer, already covered.

## Risks

**This touches nearly every line that carries a colour.** A missed pairing shows
as unreadable text — light text on a light background — and only in one mode, so
both modes are checked section by section in a browser before this is committed.

**The dark mode that exists today must not change.** Every dark value moves
across unchanged; screenshots of both modes are compared against the current
page before and after.
