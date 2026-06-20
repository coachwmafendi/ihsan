# Login Detection Alert Email

## Goal
Send an automated email to the organisation admin user immediately after a successful login, notifying them of the login details (location, browser, IP, datetime).

## Background
- Login event is already available via `Illuminate\Auth\Events\Login`.
- Existing listener `UpdateLastLoginAt` records `last_login_at`.
- Existing notification pattern: `app/Jobs/Send*Notification` jobs queue emails to all `NgoAdmin` users for that org.
- Organisation settings live in `organizations.settings` JSON column; toggles managed via `app/Livewire/App/Settings/Notifications.php`.

## Requirements
1. Trigger on every successful login by an `NgoAdmin` user that belongs to an organisation.
2. Send **only to the logged-in user** (not all admins).
3. No user-facing toggle; email always sends for qualifying logins.
4. Resolve country from public IP via `ip-api.com` free JSON API.
5. Parse user-agent string into a readable browser label.
6. Email copy matches the exact content provided by the user.

## Approach (Chosen: Option 2 — Queued Job)
```
Login event
 -> listener SendLoginAlertEmail (sync)
     -> check role == NgoAdmin and organization_id != null
     -> dispatch queued SendLoginAlertEmail job with user id, IP, UA, timestamp
         -> resolve country via ip-api.com
         -> parse browser label
         -> Mail::to($user->email)->send(LoginAlertNotification)
```

## Components

### 1. Mail: `app/Mail/LoginAlertNotification.php`
Public properties:
- `Organization $organization`
- `string $country`
- `string $ipAddress`
- `string $browser`
- `CarbonImmutable $loggedInAt`

Envelope subject: `New login to your {app.name} account`.

### 2. Job: `app/Jobs/SendLoginAlertEmail.php`
Implements `ShouldQueue`.
Constructor accepts `User $user`, `string $ipAddress`, `string $userAgent`, `CarbonImmutable $loggedInAt`.
Handle:
1. Reload user from DB.
2. Skip if user/org missing or role changed.
3. Skip private/internal IPs (`127.0.0.1`, RFC 1918 ranges) and set country to `Unknown`.
4. Call `http://ip-api.com/json/{ip}?fields=status,country,countryCode,message`.
   - On failure, fallback country = `Unknown`.
5. Parse browser from UA via new `App\Support\Browser` helper.
6. Send `LoginAlertNotification` using `MailtrapThrottle` delay.

### 3. Listener: `app/Listeners/SendLoginAlertEmail.php`
Handle `Illuminate\Auth\Events\Login`:
- Ignore SuperAdmin and users without organisation.
- Get IP via `request()->ip()`.
- Get UA via `request()->userAgent()`.
- Dispatch job.

### 4. Helper: `app/Support/Browser.php`
`parse(string $userAgent): string`
- Detect major browsers and OS from UA string: `Chrome / macOS`, `Safari / iOS`, `Firefox / Windows`, `Edge / Windows`, etc.
- Return full UA string when no match.

### 5. Blade: `resources/views/emails/login-alert-notification.blade.php`
Same layout/styling as other notification emails. Uses exact text provided:
- `Hi {org_name},`
- Location line: `{country} — {public_ip_address}`
- Browser, Date/Time
- CTA copy for password reset if unauthorized.
- Signature: `The {app.name} Team`

### 6. Registration
Register listener in `bootstrap/app.php` or existing event registration for `Illuminate\Auth\Events\Login`.

## Edge Cases / Error Handling
- Private/internal IP: skip external API, country = `Unknown`.
- ip-api.com down/over rate-limit: fallback country = `Unknown`, allow job retry.
- User deleted after job queued: skip gracefully.
- User role not NgoAdmin or no organisation: skip in both listener and job.
- Remembered session login vs fresh password login: event fires for both; acceptable.

## Verification
Write unit/feature tests:
1. Listener dispatches job for NgoAdmin login.
2. Listener ignores SuperAdmin and users without organisation.
3. Job sends `LoginAlertNotification` with expected data.
4. `Browser::parse()` returns sensible labels for common UA strings.
5. Private IP resolves to `Unknown` without external call.

## Out of Scope
- Storing login history / audit log table.
- User-facing toggle in notification settings.
- Separate email for SuperAdmin logins.
