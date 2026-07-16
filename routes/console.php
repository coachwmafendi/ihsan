<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ihsan:generate-monthly-invoices')
    ->monthlyOn(1, '08:00')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('ihsan:send-daily-summary')
    ->dailyAt('00:00')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('ihsan:send-weekly-summary')
    ->weeklyOn(1, '08:00')
    ->timezone('Asia/Kuala_Lumpur');

Schedule::command('ihsan:send-monthly-report')
    ->monthlyOn(1, '08:00')
    ->timezone('Asia/Kuala_Lumpur');

$recurringSchedule = Schedule::command('ihsan:charge-recurring-plans')
    ->dailyAt('06:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping();

$cacheDriver = config('cache.default');

if (! in_array($cacheDriver, ['file', 'database', 'array', null], true)) {
    $recurringSchedule->onOneServer();
}

Schedule::command('queue:retry all')->everySixHours();
Schedule::command('queue:prune-failed --hours=168')->weeklyOn(1, '02:00');

Schedule::command('app:cloudflare-update-ips')
    ->weeklyOn(0, '03:00')
    ->timezone('Asia/Kuala_Lumpur');
