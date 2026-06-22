<?php

use App\Enums\UserRole;
use App\Mail\DailyDonationSummary;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

it('queues daily donation summary for organization admins when enabled', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-22 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['daily_donation_summary' => true],
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
        'gross_amount' => 100.00,
        'created_at' => now()->subDay(),
    ]);

    Artisan::call('ihsan:send-daily-summary');

    Mail::assertQueued(DailyDonationSummary::class, 1);
    Mail::assertQueued(DailyDonationSummary::class, function (DailyDonationSummary $mail) use ($organization) {
        return $mail->organization->is($organization)
            && $mail->donationCount === 1
            && $mail->totalAmount === '100.00';
    });
    Mail::assertQueued(DailyDonationSummary::class, fn (DailyDonationSummary $mail) => $mail->hasTo($admin->email));
    Mail::assertNotQueued(DailyDonationSummary::class, fn (DailyDonationSummary $mail) => $mail->hasTo($otherOrganizationAdmin->email));
});

it('does not queue daily donation summary when disabled', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-22 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['daily_donation_summary' => false],
    ]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
    ]);

    Artisan::call('ihsan:send-daily-summary');

    Mail::assertNothingQueued();
});
