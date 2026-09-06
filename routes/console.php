<?php

use App\Support\SchedulerLock;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pending fees are only marked invoiced once their Stripe invoice exists, so a
// second run starting before the first finishes would bill the same fees twice.
Schedule::command('ihsan:generate-monthly-invoices')
    ->monthlyOn(1, '08:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping();

Schedule::command('ihsan:send-daily-summary')
    ->dailyAt('00:00')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('ihsan:send-weekly-summary')
    ->weeklyOn(1, '08:00')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('ihsan:send-monthly-report')
    ->monthlyOn(1, '08:00')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('ihsan:charge-recurring-plans')
    ->everyMinute()
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping();

Schedule::command('ihsan:send-payment-method-expiry-notifications')
    ->dailyAt('09:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping();

Schedule::command('queue:retry all')->everySixHours();
Schedule::command('queue:prune-failed --hours=168')->weeklyOn(1, '02:00');

// Retention is set by activitylog.clean_after_days; without this the command
// exists but never runs, so nothing is ever pruned.
Schedule::command('activitylog:clean')
    ->weeklyOn(1, '02:30')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('app:cloudflare-update-ips')
    ->weeklyOn(0, '03:00')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('app:maxmind-update')
    ->weeklyOn(1, '03:30')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('app:sync-payouts --days=7')
    ->dailyAt('05:00')
    ->timezone('Asia/Kuala_Lumpur');

// Every scheduled command here either moves money, issues invoices or emails
// supporters, so none of them may run twice when there is more than one
// container. onOneServer() can only promise that once the mutex is shared.
if (SchedulerLock::cacheIsSharedAcrossServers()) {
    foreach (Schedule::events() as $event) {
        $event->onOneServer();
    }
}
