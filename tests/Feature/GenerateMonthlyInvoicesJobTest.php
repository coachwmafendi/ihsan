<?php

declare(strict_types=1);

use App\Jobs\GenerateMonthlyInvoices;
use Illuminate\Support\Facades\Artisan;

it('calls the monthly invoice generation command', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('ihsan:generate-monthly-invoices')
        ->andReturn(0);

    $job = new GenerateMonthlyInvoices;

    $job->handle();
});
