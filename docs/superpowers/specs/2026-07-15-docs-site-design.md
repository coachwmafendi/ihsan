# Docs Site Design

**Date:** 2026-07-15
**Status:** Design ready for review

## 1. Overview

Add a public documentation site at `/docs` that feels like [Fundraise Up Docs](https://fundraiseup.com/docs/). It serves both NGO administrators (how to use the platform) and developers (how to embed and integrate).

## 2. Goals

- NGO admins can read guides covering setup, campaigns, supporters, and reports.
- Developers can read integration guides, embed code examples, and webhook/API reference.
- Content is easy to edit in Markdown, stored in Git, and deployed with the app.
- The site matches the existing Ihsan brand without depending on the Filament app shell.

## 3. Non-goals

- Full-text search across content (out of scope for first release; a nav filter is enough).
- Versioned docs (not needed yet).
- A WYSIWYG CMS in the admin panel.
- Separate subdomain or static-site generator.

## 4. Audience

1. **NGO admins** — people setting up organizations, campaigns, donation forms, and reports.
2. **Developers** — people integrating the embed widget, webhooks, or building on the public API.

## 5. Approach

Use **Laravel + static Markdown files**.

- Markdown files live in `resources/docs/`.
- A single controller, `App\Http\Controllers\DocsController`, reads the requested file, parses it to HTML, and renders it in a docs layout.
- Routing: `GET /docs/{path?}` where `path` can contain slashes.
- Navigation is declared in a PHP config/class so the sidebar, breadcrumbs, and active state are always in sync.

This keeps docs in the same repo and deployment pipeline, requires no new dependencies (`league/commonmark` is already installed), and avoids the overhead of a DB-backed CMS.

## 6. URL Structure

| URL | Content |
| --- | --- |
| `/docs` | Landing / welcome page |
| `/docs/getting-started/installation` | `resources/docs/getting-started/installation.md` |
| `/docs/run-campaigns/campaign-setup` | `resources/docs/run-campaigns/campaign-setup.md` |
| `/docs/build-integrate/embed-widget` | `resources/docs/build-integrate/embed-widget.md` |

If the requested path is a folder, the controller falls back to `index.md` inside that folder.

## 7. File Storage

```
resources/docs/
├── index.md                         # /docs landing page
├── getting-started/
│   ├── index.md
│   ├── installation.md
│   ├── payment-methods.md
│   ├── test-mode.md
│   └── organizations-and-accounts.md
├── run-campaigns/
│   ├── index.md
│   ├── campaign-setup.md
│   ├── campaign-page.md
│   ├── checkout-modal.md
│   ├── elements.md
│   └── virtual-terminal.md
├── engage-supporters/
│   ├── index.md
│   ├── donor-portal.md
│   ├── emails.md
│   ├── subscriptions.md
│   └── upgrade-links.md
├── analyze-results/
│   ├── index.md
│   ├── dashboard.md
│   ├── insights.md
│   ├── donations.md
│   ├── supporters.md
│   └── data-export.md
└── build-integrate/
    ├── index.md
    ├── embed-widget.md
    ├── javascript-api.md
    ├── webhooks.md
    └── rest-api.md
```

## 8. Controller Behavior

`App\Http\Controllers\DocsController` (invokable):

1. Read `path` route parameter (default `index`).
2. Sanitize the path and block any `..` traversal.
3. Resolve the file using `realpath()` and ensure it lives inside `resources/docs/`.
4. Try the exact `.md` file first, then fall back to `{path}/index.md`.
5. Abort 404 if the file cannot be found.
6. Parse Markdown to HTML with `league/commonmark`, including the `TableExtension` for GFM-style tables.
7. Extract the page title from the first `<h1>` in the rendered HTML.
8. Render `docs.show` with navigation, HTML content, and title.

## 9. Layout and UI

Create a dedicated `resources/views/layouts/docs.blade.php` that:

- Uses the same fonts and Vite assets as the main app.
- Sets a light background (`bg-slate-50 text-slate-900`) for extended reading.
- Includes a slim top navigation bar with the Ihsan logo, a language switcher, and a link back to the app/marketing site.
- Provides a responsive sidebar for navigation (collapsible on mobile via Alpine.js).
- Wraps content in a `prose`-style container for good Markdown typography.

The main view `resources/views/docs/show.blade.php`:

- `x-slot:title` from the first `<h1>`.
- Renders the Markdown HTML.
- Shows breadcrumbs built from the navigation config.

## 10. Navigation

Navigation is declared in `config/docs.php` (or `App\Docs\Navigation`) as a hierarchical array of sections and pages. This is used for:

- Rendering the sidebar.
- Marking the active item.
- Building breadcrumbs.
- Ordering categories and pages deterministically.

Each item has:

```php
[
    'label' => 'Run campaigns',
    'slug' => 'run-campaigns',
    'children' => [
        ['label' => 'Campaign setup', 'slug' => 'campaign-setup'],
        // ...
    ],
]
```

The controller resolves the active path against this config and passes it to the view.

## 11. Code Highlighting

Include `highlight.js` from a CDN in `layouts/docs.blade.php`, loaded only on docs pages. It handles fenced code blocks for JavaScript, HTML, PHP, JSON, and Bash without adding a build dependency. All code examples in Markdown use triple backticks.

## 12. Styling Conventions

- Docs use Tailwind utility classes and `@tailwindcss/typography` `prose` classes.
- Light theme with teal accent color to match the existing marketing site.
- Tables, blockquotes, and code blocks need clear visual boundaries.
- Admonition blocks (`Note`, `Warning`, `Tip`) can be added later via custom Markdown if needed; first release skips them.

## 13. Error Handling

- Missing or invalid path → 404.
- Directory traversal attempt → 404 (do not reveal file structure).
- Malformed Markdown still parses; a missing `<h1>` falls back to a title from navigation config or the app name.

## 14. Testing

Add Pest feature tests in a new `tests/Feature/Docs/DocsControllerTest.php`:

- The landing page `/docs` returns 200.
- A known article like `/docs/build-integrate/embed-widget` returns 200 and contains expected text.
- A missing path returns 404.
- Path traversal (`/docs/../composer.json`) returns 404.
- GFM tables are rendered as `<table>`.
- Navigation active state is set for the current page.

## 15. Dependencies

No new Composer or npm packages required.

- `league/commonmark` is already available via Laravel framework.
- `League\CommonMark\Extension\Table\TableExtension` is available for GFM tables.

## 16. Future Work (out of scope)

- Full-text search (server-side index or Algolia DocSearch).
- Multilingual docs (`resources/docs/{locale}/...`).
- Admonition callouts, page edit links on GitHub.
- API reference generated from OpenAPI or route annotations.

## 17. Open Questions

1. Should the public marketing site top nav include a "Docs" link?
2. Should docs pages support a right-hand "On this page" auto-generated TOC from headings?
3. Do we need any docs pages restricted to authenticated NGO admins? (First release: all public.)
