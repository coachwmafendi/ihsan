<?php

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Livewire\App\Reports\MonthlyDonations;
use App\Mail\WeeklyDonationSummary;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
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

/**
 * Runs the weekly summary for 24-30 Aug 2026 with one admin and returns the
 * queued mailable.
 */
function sendWeekOf24AugFor(Organization $organization): WeeklyDonationSummary
{
    Mail::fake();

    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);

    Artisan::call('ihsan:send-weekly-summary');

    $queued = null;
    Mail::assertQueued(WeeklyDonationSummary::class, function (WeeklyDonationSummary $mail) use (&$queued): bool {
        $queued = $mail;

        return true;
    });

    return $queued;
}

function insideWeekOf24Aug(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-08-26 10:00:00', 'Asia/Kuala_Lumpur')->utc();
}

it('lists campaigns that are no longer active when they received donations, so the breakdown adds up', function () {
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    $active = Campaign::factory()->for($organization)->create(['title' => 'A Active', 'status' => CampaignStatus::Active]);
    $ended = Campaign::factory()->for($organization)->create(['title' => 'B Ended', 'status' => CampaignStatus::Ended]);
    Campaign::factory()->for($organization)->create(['title' => 'C Ended quietly', 'status' => CampaignStatus::Ended]);

    Donation::factory()->for($ended)->create(['gross_amount' => 80, 'base_amount' => 80, 'created_at' => insideWeekOf24Aug()]);

    $mail = sendWeekOf24AugFor($organization);

    expect(collect($mail->campaigns)->pluck('total', 'title')->all())
        ->toBe(['A Active' => '0.00', 'B Ended' => '80.00']);
});

it('marks only the campaigns that mix in other currencies as approximate', function () {
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    $ringgitOnly = Campaign::factory()->for($organization)->create(['title' => 'A Ringgit']);
    $mixed = Campaign::factory()->for($organization)->create(['title' => 'B Mixed']);

    Donation::factory()->for($ringgitOnly)->create(['gross_amount' => 100, 'base_amount' => 100, 'created_at' => insideWeekOf24Aug()]);
    Donation::factory()->for($mixed)->create(['gross_amount' => 10, 'currency' => 'sgd', 'base_amount' => 31.8, 'created_at' => insideWeekOf24Aug()]);

    $mail = sendWeekOf24AugFor($organization);

    expect(collect($mail->campaigns)->pluck('approximate', 'title')->all())
        ->toBe(['A Ringgit' => false, 'B Mixed' => true]);
});

it('does not send the same week twice', function () {
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    User::factory()->create(['organization_id' => $organization->getKey(), 'role' => UserRole::NgoAdmin]);
    Campaign::factory()->for($organization)->create();

    Artisan::call('ihsan:send-weekly-summary');
    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertQueued(WeeklyDonationSummary::class, 1);
    expect($organization->refresh()->settings['weekly_report_last_sent'])->toBe('2026-08-24');
});

it('does not mark the week sent when the organization has no admin to receive it', function () {
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    Campaign::factory()->for($organization)->create();

    Artisan::call('ihsan:send-weekly-summary');

    Mail::assertNothingQueued();
    expect($organization->refresh()->settings['weekly_report_last_sent'] ?? null)->toBeNull();
});

it('names the organization in the subject', function () {
    $mail = new WeeklyDonationSummary(
        organization: Organization::factory()->make(['name' => 'Masjid Al Ayubi']),
        donationCount: 0,
        totalAmount: '0.00',
        campaigns: [],
        period: '24 Aug – 30 Aug 2026',
    );

    expect($mail->envelope()->subject)->toBe('Masjid Al Ayubi — Weekly donation report, 24 Aug – 30 Aug 2026');
});

it('compares the total with the week before', function () {
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    $campaign = Campaign::factory()->for($organization)->create();

    Donation::factory()->for($campaign)->create(['gross_amount' => 200, 'base_amount' => 200, 'created_at' => CarbonImmutable::parse('2026-08-19 10:00:00', 'Asia/Kuala_Lumpur')->utc()]);
    Donation::factory()->for($campaign)->create(['gross_amount' => 150, 'base_amount' => 150, 'created_at' => insideWeekOf24Aug()]);

    $mail = sendWeekOf24AugFor($organization);

    expect($mail->previousTotalAmount)->toBe('200.00')
        ->and($mail->changePercent)->toBe(-25.0);
});

it('reports monthly plans started, cancelled and past due', function () {
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    $campaign = Campaign::factory()->for($organization)->create();

    Subscription::factory()->for($campaign)->create(['created_at' => insideWeekOf24Aug()]);
    Subscription::factory()->for($campaign)->create(['status' => SubscriptionStatus::Incomplete, 'created_at' => insideWeekOf24Aug()]);
    Subscription::factory()->for($campaign)->create(['status' => SubscriptionStatus::Cancelled, 'created_at' => '2026-01-01 12:00:00', 'cancelled_at' => insideWeekOf24Aug()]);
    Subscription::factory()->for($campaign)->create(['status' => SubscriptionStatus::PastDue, 'created_at' => '2026-01-01 12:00:00']);
    Subscription::factory()->for($campaign)->create(['status' => SubscriptionStatus::PastDue, 'created_at' => '2026-01-01 12:00:00']);

    $mail = sendWeekOf24AugFor($organization);

    expect($mail->plans)->toBe([
        'new_plan_count' => 1,
        'cancelled_plan_count' => 1,
        'past_due_plan_count' => 2,
    ]);
});

it('links to the report page for the week', function () {
    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:00:00', 'Asia/Kuala_Lumpur'));

    $organization = Organization::factory()->create(['settings' => ['weekly_report' => true]]);
    Campaign::factory()->for($organization)->create();

    $mail = sendWeekOf24AugFor($organization);

    expect($mail->reportUrl)->toEndWith('/reports/monthly-donations?from=2026-08-24&to=2026-08-30');
});

it('opens the report page at the dates in the link', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    Livewire::withQueryParams(['from' => '2026-08-24', 'to' => '2026-08-30'])
        ->actingAs($user)
        ->test(MonthlyDonations::class)
        ->assertSet('customRange', true)
        ->assertSet('dateFrom', '2026-08-24')
        ->assertSet('dateTo', '2026-08-30');
});

it('ignores dates in the link that are not dates', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    Livewire::withQueryParams(['from' => 'nope', 'to' => '2026-08-30'])
        ->actingAs($user)
        ->test(MonthlyDonations::class)
        ->assertSet('customRange', false);
});

it('renders the change, plans and link inside the email', function () {
    $html = (new WeeklyDonationSummary(
        organization: Organization::factory()->create(['name' => 'Masjid Al Ayubi']),
        donationCount: 3,
        totalAmount: '150.00',
        campaigns: [['title' => 'Dana', 'count' => 3, 'total' => '150.00', 'approximate' => false]],
        period: '24 Aug – 30 Aug 2026',
        previousTotalAmount: '200.00',
        changePercent: -25.0,
        plans: ['new_plan_count' => 1, 'cancelled_plan_count' => 0, 'past_due_plan_count' => 2],
        reportUrl: 'https://app.example.test/reports/monthly-donations?from=2026-08-24&to=2026-08-30',
    ))->render();

    expect($html)
        ->toContain('▼ 25%')
        ->toContain('MYR 200.00')
        ->toContain('Past due now')
        ->toContain('https://app.example.test/reports/monthly-donations?from=2026-08-24&amp;to=2026-08-30');
});
