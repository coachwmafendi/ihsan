<?php

use App\Enums\UserRole;
use App\Mail\WeeklyDonationSummary;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

it('queues weekly donation summary for organization admins when enabled', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-22 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['weekly_report' => true],
    ]);
    $admin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'admin@example.test',
        'role' => UserRole::NgoAdmin,
    ]);
    $otherOrganizationAdmin = User::factory()->create([
        'email' => 'other-admin@example.test',
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->create([
        'organization_id' => $organization->getKey(),
        'title' => 'Bantuan Makanan',
    ]);

    Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'gross_amount' => 200.00,
        'created_at' => now()->subWeek()->addDay(),
    ]);

    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertQueued(WeeklyDonationSummary::class, 1);
    Mail::assertQueued(WeeklyDonationSummary::class, function (WeeklyDonationSummary $mail) use ($organization) {
        return $mail->organization->is($organization)
            && $mail->donationCount === 1
            && $mail->totalAmount === '200.00';
    });
    Mail::assertQueued(WeeklyDonationSummary::class, fn (WeeklyDonationSummary $mail) => $mail->hasTo($admin->email));
    Mail::assertNotQueued(WeeklyDonationSummary::class, fn (WeeklyDonationSummary $mail) => $mail->hasTo($otherOrganizationAdmin->email));
});

it('does not queue weekly donation summary when disabled', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-22 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['weekly_report' => false],
    ]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
    ]);

    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertNothingQueued();
});
