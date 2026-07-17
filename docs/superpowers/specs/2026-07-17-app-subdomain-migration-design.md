# App Subdomain Migration — Design

**Date:** 2026-07-17
**Status:** Approved

## Problem

The NGO admin panel lives at `getihsan.my/app/*` as a path prefix. We want it at
`app.getihsan.my/*` as a proper subdomain. The only subdomain support today is a
single redirect route (`routes/web.php:55`) that sends `app.getihsan.my/` back to
`getihsan.my/app/dashboard` when `APP_PANEL_DOMAIN` is set.

## Decisions

| Decision | Choice |
|---|---|
| Old `/app/*` URLs | 301 redirect to subdomain (path + query preserved) |
| NGO auth (Fortify) | Moves to subdomain via `config/fortify.php` `domain` |
| Local dev | Mirrors prod: `app.ihsan.test` via Herd (no dual-mode routing) |
| Route structure | New `routes/app.php` under `Route::domain()`; `web.php` keeps public routes |
| Route names | Unchanged (`app.*`) — zero blade/component edits for URL generation |
| Session scope | `SESSION_DOMAIN` stays `null`; cookies host-scoped per domain |

## Routing

- **New `routes/app.php`**, registered in `bootstrap/app.php`. Contains every current
  `/app/*` route with the `/app` prefix stripped (`/dashboard`, `/campaigns`,
  `/donations`, `/supporters`, `/subscriptions`, `/elements`, `/settings/*`,
  `/billing`, `/virtual-terminal`, `/audit-log`, `/notifications`, exports,
  impersonation, Stripe onboarding). All wrapped in
  `Route::domain(config('app.app_panel_domain'))` with existing middleware
  (`auth`, `EnsureNgoAdmin`, `RedirectIfStripeNotOnboarded`) intact.
- Subdomain `/` redirects to `/dashboard` (replaces the old `app.home` redirect).
- **`web.php` keeps public routes only:** landing, campaign public pages, docs,
  embed (`/e/*`), Stripe/CHIP webhooks and callbacks, donor portal
  (`/donorportal/*`), Filament superadmin (`/admin`), register-organization.
  Webhook URLs registered with Stripe/CHIP are unchanged.

## Redirects (301)

On the root domain:

- `GET /app/{path?}` (catch-all, `where path .*`) → `https://{APP_PANEL_DOMAIN}/{path}`,
  query string preserved.
- `GET /login` → subdomain `/login`.

## Auth & Session

- `config/fortify.php`: `'domain' => env('APP_PANEL_DOMAIN')`. Login, logout, 2FA,
  password reset, email verification all register on the subdomain.
- `SESSION_DOMAIN=null` (unchanged). Panel session cookie scoped to
  `app.getihsan.my` only. Donor portal and Filament `/admin` on the root domain
  keep independent host-scoped cookies. No cross-subdomain cookie surface.

## Config / Env

- `config/app.php` `app_panel_domain` already exists (reads `APP_PANEL_DOMAIN`).
- Local `.env`: `APP_PANEL_DOMAIN=app.ihsan.test` (Herd serves subdomains of
  linked sites automatically).
- Production: `APP_PANEL_DOMAIN=app.getihsan.my`.
- `phpunit.xml`: set `APP_PANEL_DOMAIN` so tests exercise the real domain routing.
- `.env.example`: document the variable.
- `APP_PANEL_DOMAIN` becomes required (no path-prefix fallback mode).

## Infrastructure (before code deploy)

1. Cloudflare DNS: `app` CNAME to the same origin, proxied.
2. Coolify: add `app.getihsan.my` to the application FQDNs.
3. Verify TLS on the subdomain, then deploy code.

## Testing

- Fix any tests hitting literal `/app/...` paths (they would hit 301s).
- Grep blades/JS for hardcoded `/app/` links and fix.
- New tests: dashboard serves on the subdomain; old `/app/*` paths 301 with path
  and query preserved; root `/login` redirects; Fortify login works on the
  subdomain.

## Known Effects / Risks

- **One-time session drop at deploy:** the session cookie host changes, so all
  logged-in NGO users must log in again once. Accepted.
- Old links in previously sent emails keep working via the 301 catch-all.
