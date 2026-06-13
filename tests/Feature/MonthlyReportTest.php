<?php

use App\Enums\UserRole;
use App\Mail\MonthlyReport;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

it('queues previous month report for organizations that enabled monthly reports', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['monthly_report' => true],
    ]);
    $admin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'admin@example.test',
        'role' => UserRole::NgoAdmin,
    ]);
    $superAdmin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'super-admin@example.test',
        'role' => UserRole::SuperAdmin,
    ]);
    $campaign = Campaign::factory()->create([
        'organization_id' => $organization->getKey(),
        'title' => 'Bantuan Makanan',
    ]);

    Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'gross_amount' => 150.00,
        'created_at' => now()->parse('2026-05-15 12:00:00'),
    ]);
    Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'gross_amount' => 75.00,
        'created_at' => now()->parse('2026-06-01 00:00:00'),
    ]);

    Artisan::call('ihsan:send-monthly-report');

    Mail::assertQueued(MonthlyReport::class, function (MonthlyReport $mail) use ($admin, $organization) {
        return $mail->hasTo($admin->email)
            && $mail->organization->is($organization)
            && $mail->period === 'May 2026'
            && $mail->donationCount === 1
            && $mail->totalAmount === '150.00'
            && $mail->campaigns === [[
                'title' => 'Bantuan Makanan',
                'count' => 1,
                'total' => '150.00',
            ]];
    });
    Mail::assertNotQueued(MonthlyReport::class, fn (MonthlyReport $mail) => $mail->hasTo($superAdmin->email));

    expect($organization->refresh()->settings['monthly_report_last_sent'])->toBe('2026-05-01');
});

it('does not queue monthly report when disabled', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['monthly_report' => false],
    ]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
    ]);

    Artisan::call('ihsan:send-monthly-report');

    Mail::assertNothingQueued();
});
