<?php

use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Mail\WeeklyDonationSummary;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
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
        'base_amount' => 200.00,
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

it('does not queue weekly donation summary for soft-deleted organizations', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-22 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['weekly_report' => true],
    ]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'admin@example.test',
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->create([
        'organization_id' => $organization->getKey(),
    ]);
    Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'gross_amount' => 50.00,
        'created_at' => now()->subWeek()->addDay(),
    ]);

    $organization->delete();

    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertNothingQueued();
});

it('reports the MYR equivalent when a week mixes currencies', function () {
    // Reproduces a real report: MASJID TAHFIZ AL AYUBI's week of 24-30 Aug
    // 2026 held 5 MYR donations worth 510.00 and 17 SGD donations worth
    // 910.00 SGD. Adding the two currencies together billed the week as
    // "MYR 1,420.00" when the MYR equivalent was 3,402.78.
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();

    $insideTheWeek = CarbonImmutable::parse('2026-08-26 10:00:00', 'Asia/Kuala_Lumpur')->utc();

    Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'myr',
        'gross_amount' => 510.00,
        'base_amount' => 510.00,
        'created_at' => $insideTheWeek,
    ]);

    Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'sgd',
        'gross_amount' => 910.00,
        'base_amount' => 2892.78,
        'created_at' => $insideTheWeek,
    ]);

    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertQueued(WeeklyDonationSummary::class, function (WeeklyDonationSummary $mail): bool {
        return $mail->totalAmount === '3,402.78' && $mail->hasApproximation === true;
    });
});

it('does not flag a single-currency week as approximate', function () {
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();

    Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'myr',
        'gross_amount' => 250.00,
        'base_amount' => 250.00,
        'created_at' => CarbonImmutable::parse('2026-08-26 10:00:00', 'Asia/Kuala_Lumpur')->utc(),
    ]);

    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertQueued(WeeklyDonationSummary::class, function (WeeklyDonationSummary $mail): bool {
        return $mail->totalAmount === '250.00' && $mail->hasApproximation === false;
    });
});

it('measures the reported week in Malaysian time', function () {
    // A donation at 00:30 MYT on Monday 31 Aug belongs to the new week, but a
    // UTC week boundary would still count it in the week being reported.
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();

    Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'myr',
        'gross_amount' => 99.00,
        'base_amount' => 99.00,
        'created_at' => CarbonImmutable::parse('2026-08-31 00:30:00', 'Asia/Kuala_Lumpur')->utc(),
    ]);

    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertQueued(WeeklyDonationSummary::class, function (WeeklyDonationSummary $mail): bool {
        return $mail->donationCount === 0 && $mail->period === '24 Aug – 30 Aug 2026';
    });
});
