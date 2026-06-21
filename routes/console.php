<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ihsan:generate-monthly-invoices')->monthlyOn(1, '08:00');
Schedule::command('ihsan:send-daily-summary')->dailyAt('00:00');
Schedule::command('ihsan:send-weekly-summary')->weeklyOn(1, '08:00');
Schedule::command('ihsan:send-monthly-report')->monthlyOn(1, '08:00');

Schedule::command('queue:retry all')->everySixHours();
Schedule::command('queue:prune-failed --hours=168')->weeklyOn(1, '02:00');
