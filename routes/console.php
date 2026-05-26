<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ihsan:generate-monthly-invoices')->monthlyOn(1, '08:00');
Schedule::command('ihsan:send-daily-summary')->everyThirtyMinutes();
Schedule::command('ihsan:send-monthly-report')->monthlyOn(1, '09:00');
