# MYT Timezone Display Design

## Goal
Display all datetime values in the frontend using Malaysia Time (`Asia/Kuala_Lumpur`, UTC+8) with an `(MYT)` label, while keeping the application and database timezone as UTC.

## Decisions
- `diffForHumans()` values remain unchanged (e.g. "2 hours ago"). Timezone labels read awkwardly with relative time.
- Date-only values (e.g. `22 Jun 2026`) remain unchanged — no timezone label when time is not shown.
- Datetime values (with time) are shown as: `22 Jun 2026, 2:30 PM (MYT)`.
- Application timezone stays UTC.

## Implementation

### 1. Helper
Add to existing `app/helpers.php`:

```php
if (! function_exists('myrTime')) {
    function myrTime(?\Illuminate\Support\Carbon $datetime, bool $withLabel = true, string $format = 'd M Y, h:i A'): string
    {
        if (! $datetime) {
            return '—';
        }

        $time = $datetime->timezone('Asia/Kuala_Lumpur')->format($format);

        return $withLabel ? "{$time} (MYT)" : $time;
    }
}
```

### 2. Optional Blade directive
Register a `@myrtime` directive in `App\Providers\AppServiceProvider`:

```php
Blade::directive('myrtime', function ($expression) {
    return "<?php echo myrTime({$expression}); ?>";
});
```

### 3. Update frontend displays
Replace datetime formatting in these areas:
- Donor/public pages
- App dashboard, donations, supporters, subscriptions, campaigns, elements pages
- Admin pages
- Email views and PDF receipt
- Filament table columns

For Blade views use `myrTime($model->created_at)` or `@myrtime($model->created_at)`.

For Filament PHP columns use `->dateTime('d M Y, h:i A', timezone: 'Asia/Kuala_Lumpur')` and append `(MYT)` via `->formatStateUsing()` or use the helper.

### 4. Leave unchanged
- `diffForHumans()` calls.
- Pure date formatting without time (`d M Y`, `M d, Y`).

## Testing
Run the existing test suite to ensure no regressions from format changes.
