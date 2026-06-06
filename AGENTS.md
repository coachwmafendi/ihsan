<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

## Anchored Summary

### Goal
- Build notification settings UI and email sending logic, set up Stripe Connect payment page, add period/donor filters, redesign Popup element form, and generate embed codes per element type

### Constraints & Preferences
- Use Livewire/Filament pages for settings (not standalone form)
- Save preferences to `organizations.settings` JSON column
- Toggles auto-save on change (Livewire `$toggle` + `updated()` hook)
- Send emails via queue jobs; check org setting before sending
- All queries must be DB-agnostic (SQLite in tests)
- Popup element form uses simplified config fields (title, message, button_text, action, trigger, delay, frequency, visibility, layout, image, color) — removed old donation-form config
- Embed scripts all point to single `/e/widget.js` with `data-type` attribute, not separate JS files per type
- Embed code copied via Alpine `@js()` + `navigator.clipboard.writeText()` — not stored in `data-*` HTML attributes (avoids HTML entity encoding)

### Progress
#### Done
- **Settings refactored**: `Settings.php` split into 3 pages under Settings group — `ProfilOrganisasi`, `Pembayaran`, `Pemberitahuan`
- **Notification toggles**: 9 toggles (new donation, daily summary, failed payment, new subscription, subscription cancelled, large donation + threshold, refund, campaign milestone, monthly report)
- **Livewire 4 fix**: `wire:model.blur` → `wire:model.live.blur` so field changes actually send a network request to the server
- **Mail classes**: `NewDonationNotification`, `DailyDonationSummary`, `FailedPaymentNotification`, `NewSubscriptionNotification`, `SubscriptionCancelledNotification`, `LargeDonationNotification`, `RefundNotification`
- **Jobs**: `SendNewDonationNotification`, `SendFailedPaymentNotification`, `SendNewSubscriptionNotification`, `SendSubscriptionCancelledNotification`, `SendLargeDonationNotification`, `SendRefundNotification`, `SendDailyDonationSummary`
- **Dispatch points in ProcessStripeWebhook**: `handlePaymentIntentSucceeded` (new sub + large donation), `handleSubscriptionDeleted` (cancelled), `handleChargeRefunded` (new handler)
- **Revenue page fixed**: hardcoded "3%" replaced with config-driven 2.5%, effective fee rate from actual data, all processing fees counted (not just `paid`)
- **Negeri (state)**: `TextInput` → `Select` with 16 Malaysia states, searchable
- **Negara (country)**: `TextInput` → `Select` with 21 common countries, searchable
- **StripePaymentIntentController**: stashed off-platform premium payment endpoint — disabled in Filament route registration, accessible only via direct URL
- **Stripe Connect text**: Pembayaran page headings, button labels, modal text — renamed "Stripe" → "Stripe Connect"
- **Period filter (donations table)**: Insights-style Alpine dropdown with All Time, Today, Yesterday, Last 7 days, Last 30 days, Last 90 days, This month — replaces old `SelectFilter` and `getTabs()` approaches
- **Donor country**: added `donor_country` VARCHAR(2) column to `donations`, extracted from `$paymentMethod->card->country` in `SyncDonationStripeDetails`, saved on webhook sync
- **Popup element form redesigned**: removed old donation-form config (template, colors, amounts, popup triggers etc.) with simplified sections: Content (title, message, button_text), Action (campaign_page/checkout_modal), Display Rules (trigger, delay, frequency, visibility), Appearance (layout, image, color), Status toggle
- **PDF receipt redesigned**: formal receipt document with header, donor info, table (amount, campaign, type, payment method, status), tax-exempt footer — removed promotional language
- **Element type options**: explicit proper case labels (`Button`, `Floating Button`, `Form`, `Popup`) instead of `ElementType::class` enum auto-generation, fixed `->value` on string error
- **Embed code generation**: per-type embed snippet shown after element is saved — Floating Button (script with all data attrs), Button (script), Popup (script), Form (iframe) — all point to `/e/widget.js` with `data-type` attribute
- **Embed code copy fix**: uses Alpine `@js()` directive + `x-text` rendering instead of `data-code` attribute — avoids HTML entity encoding (`&lt;`/`&gt;`) on paste
- **Embed token visibility**: donation page URL + QR + WhatsApp share only shown for Form/Popup types, hidden for Button/FloatingButton
- **Route + controller for `/e/widget.js`**: `EmbedCheckoutController@widget` returns widget JS that renders floating buttons, inline buttons, and popups from `data-*` attributes on the script tag

#### In Progress
- *(none)*

#### Blocked
- *(none)*

### Key Decisions
- Custom HTML toggle buttons (`wire:click="$toggle()"`) instead of `<flux:switch>` — Flux JS not loaded in Filament pages
- Mail sent to all `NgoAdmin` users in the org, not just primary contact
- Defaults: `notify_new_donation` ON, `daily_donation_summary` OFF, `failed_payment_notification` ON, `notify_new_subscription` ON, `notify_subscription_cancelled` ON, `notify_large_donation` OFF, `notify_refund` ON, `notify_campaign_milestone` OFF, `monthly_report` OFF
- Revenue page counts all `ProcessingFee` records (not only `paid`) — pending fees are legitimate collections
- Period filter uses Alpine dropdown button (not Filament tabs) to match Insights UX
- Donor country stored on donation record (not donor) — available at charge sync time without additional API calls
- Popup element no longer shares config with Form type — separate `defaultConfigForType` case with its own defaults
- Embed code uses `@js()` for Alpine data binding instead of HTML `data-*` attributes — avoids HTML entity corruption on copy

### Next Steps
1. Create widget JS file `/e/widget.js` route + controller that serves the widget JavaScript dynamically based on element token/config — **DONE**
2. Build the JS widget that renders floating button/popup/button elements from data attributes — **DONE**
3. Verify embed code renders correctly on external sites

### Done: `public_id` Implementation
Add `public_id` to 7 tables for public-facing UI and URLs (hide auto-increment IDs). **All tasks completed — 213 tests passing.**

#### Format Rules
- **8 characters total**, uppercase A–Z + digits **1–9** (no 0).
- **Prefix per table** (fixed) + random characters.
- Must be **unique** per table; retry on collision (max 10 attempts).

| Table | Prefix | Random Characters | Example |
|-------|--------|-------------------|---------|
| `users` | `U` | 7 | `UAB3C9D2` |
| `campaigns` | `IH` | 6 | `IH7A3B9C` |
| `donors` | `DR` | 6 | `DR2E8F1G` |
| `donations` | `D` | 7 | `D4H5I6J7` |
| `subscriptions` | `R` | 7 | `R8K9L1M2` |
| `elements` | `E` | 7 | `E3N4O5P6` |
| `monthly_invoices` | `I` | 7 | `I7Q8R9S1` |

#### Tasks Completed
1. **Migrations** — `2026_06_06_092454_add_public_id_to_multiple_tables.php` adds nullable `public_id` VARCHAR(8) with unique index to all 7 tables.
2. **ID Generator Service** — `app/Services/PublicIdGenerator.php`: configurable prefix/length per model, charset `A-Z,1-9`, existence check + retry loop.
3. **Backfill Command** — `php artisan app:backfill-public-ids` processes all 7 models with chunked queries and progress bars.
4. **Model Observers** — `booted()` `creating` hook on all 7 models auto-generates `public_id` if blank.
5. **Factories** — all 7 factories include `'public_id' => null` so observer generates it during tests.
6. **Tests** — `tests/Unit/PublicIdGeneratorTest.php` (format validation, unsupported model exception) + `tests/Feature/PublicIdModelTest.php` (auto-generation for all 7 models, collision retry, manual override, uniqueness, backfill command).

### Critical Context
- `php artisan test` exit code 0 — 111 passed, 2 skipped (pre-existing cURL timeout in `PlatformInvoicePaid` mail test from factory-generated fake URLs)
- Livewire 4: `wire:model.blur` without `.live` only syncs client-side (Alpine `$wire` proxy), does not send a network request — `updated()` hook never fires
- `Pemberitahuan.php` slug is `pemberitahuan`, which doesn't match the old Settings page — any deep links to `/app/settings` will 404
- `selectedType()` helper converts Popup → Form for visibility logic; use `$get('type') === 'popup'` directly for Popup-specific sections

### Relevant Files
- `app/Filament/App/Pages/Pemberitahuan.php`: Page class with all 9 notification toggles, auto-save, mount
- `resources/views/filament/app/pages/pemberitahuan.blade.php`: Notifications view with toggle cards + daily summary time input + large donation threshold input
- `app/Filament/Pages/Revenue.php`: Updated to use config-driven fee rate, all processing fees, effective rate from actual data
- `resources/views/filament/admin/pages/revenue.blade.php`: Updated cards showing dynamic values
- `app/Filament/App/Pages/ProfilOrganisasi.php`: Profile page with state/country `Select` fields
- `app/Filament/App/Pages/Pembayaran.php`: Stripe connection page with Stripe Connect text/labels
- `app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`: Admin org form with state/country `Select` fields
- `app/Jobs/ProcessStripeWebhook.php`: Dispatch points for all new notification types + `handleChargeRefunded`
- `app/Jobs/Send{NewSubscription,SubscriptionCancelled,LargeDonation,Refund}Notification.php`: 4 new notification jobs
- `app/Mail/{NewSubscription,SubscriptionCancelled,LargeDonation,Refund}Notification.php`: 4 new mailables
- `resources/views/emails/{new-subscription,subscription-cancelled,large-donation,refund}-notification.blade.php`: 4 new email views
- `app/Console/Commands/SendDailyDonationSummary.php`: Scheduled command for daily summary
- `app/Livewire/DonationForm.php`: Dispatch `SendNewDonationNotification` on success
- `routes/console.php`: Daily summary schedule
- `app/Filament/App/Resources/Donations/Pages/ListDonations.php`: Period filter with Alpine dropdown, `getTableQuery()` override for date range
- `resources/views/filament/app/resources/donations/pages/period-filter.blade.php`: Alpine dropdown for period filter
- `app/Actions/Stripe/SyncDonationStripeDetails.php`: Extracts and saves `donor_country` from PaymentMethod card
- `database/migrations/2026_05_24_121101_add_donor_country_to_donations_table.php`: Adds `donor_country` VARCHAR(2) column
- `app/Filament/App/Resources/Elements/Schemas/ElementForm.php`: Popup form redesign + embed-token visibility per type
- `resources/views/emails/donation-receipt-pdf.blade.php`: Formal PDF receipt template
- `resources/views/filament/forms/components/element-embed-snippet.blade.php`: Per-type embed code display with copy button, uses `/e/widget.js`
- `app/Http/Controllers/EmbedCheckoutController.php`: Added `widget()` method serving `/e/widget.js` with floating button/button/popup renderers
- `routes/web.php`: Added `Route::get('/e/widget.js', ...)`
