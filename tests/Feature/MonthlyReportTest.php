<?php

use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Livewire\App\Reports\MonthlyDonations;
use App\Mail\MonthlyReport;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

it('queues previous month report for organization admins when monthly reports are enabled', function () {
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
    $secondAdmin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'second-admin@example.test',
        'role' => UserRole::NgoAdmin,
    ]);
    $superAdmin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'super-admin@example.test',
        'role' => UserRole::SuperAdmin,
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
        'gross_amount' => 150.00,
        'base_amount' => 150.00,
        'created_at' => now()->parse('2026-05-15 12:00:00'),
    ]);
    Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'gross_amount' => 75.00,
        'base_amount' => 75.00,
        'created_at' => now()->parse('2026-06-01 00:00:00'),
    ]);

    Artisan::call('ihsan:send-monthly-report');

    Mail::assertQueued(MonthlyReport::class, 2);
    Mail::assertQueued(MonthlyReport::class, function (MonthlyReport $mail) use ($organization) {
        return $mail->organization->is($organization)
            && $mail->period === 'May 2026'
            && $mail->donationCount === 1
            && $mail->totalAmount === '150.00'
            && $mail->campaigns === [[
                'title' => 'Bantuan Makanan',
                'count' => 1,
                'total' => '150.00',
                'approximate' => false,
            ]];
    });
    Mail::assertQueued(MonthlyReport::class, fn (MonthlyReport $mail) => $mail->hasTo($admin->email));
    Mail::assertQueued(MonthlyReport::class, fn (MonthlyReport $mail) => $mail->hasTo($secondAdmin->email));
    Mail::assertNotQueued(MonthlyReport::class, fn (MonthlyReport $mail) => $mail->hasTo($superAdmin->email));
    Mail::assertNotQueued(MonthlyReport::class, fn (MonthlyReport $mail) => $mail->hasTo($otherOrganizationAdmin->email));

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

it('does not queue monthly report for soft-deleted organizations', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create([
        'settings' => ['monthly_report' => true],
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
        'gross_amount' => 150.00,
        'base_amount' => 150.00,
        'created_at' => now()->parse('2026-05-15 12:00:00'),
    ]);

    $organization->delete();

    Artisan::call('ihsan:send-monthly-report');

    Mail::assertNothingQueued();
    expect($organization->refresh()->settings['monthly_report_last_sent'] ?? null)->toBeNull();
});

it('keeps the month the user picked', function () {
    // canBeCreatedFromFormat takes the date first. Reversed, it asked whether
    // the literal "Y-m" was a date - always no - so every chosen month was
    // discarded and the report snapped back to the current one.
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test(MonthlyDonations::class)
        ->set('selectedMonth', '2026-08')
        ->assertSet('selectedMonth', '2026-08')
        ->assertSet('dateFrom', '2026-08-01')
        ->assertSet('dateTo', '2026-08-31');
});

it('falls back to the current month when the value is nonsense', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $component = Livewire::actingAs($user)
        ->test(MonthlyDonations::class)
        ->set('selectedMonth', 'not-a-month');

    expect($component->get('selectedMonth'))->toMatch('/^\d{4}-\d{2}$/');
});

/**
 * Runs the May 2026 report for one organization with a single admin and
 * returns the queued mailable.
 */
function sendMayReportFor(Organization $organization): MonthlyReport
{
    Mail::fake();

    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);

    Artisan::call('ihsan:send-monthly-report');

    $queued = null;
    Mail::assertQueued(MonthlyReport::class, function (MonthlyReport $mail) use (&$queued): bool {
        $queued = $mail;

        return true;
    });

    return $queued;
}

it('marks only the campaigns that mix in other currencies as approximate', function () {
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);
    $ringgitOnly = Campaign::factory()->create(['organization_id' => $organization->getKey(), 'title' => 'A Ringgit']);
    $mixed = Campaign::factory()->create(['organization_id' => $organization->getKey(), 'title' => 'B Mixed']);

    Donation::factory()->create(['campaign_id' => $ringgitOnly->getKey(), 'gross_amount' => 100, 'base_amount' => 100, 'created_at' => '2026-05-10 12:00:00']);
    Donation::factory()->create(['campaign_id' => $mixed->getKey(), 'gross_amount' => 10, 'currency' => 'usd', 'base_amount' => 44.5, 'created_at' => '2026-05-11 12:00:00']);

    $mail = sendMayReportFor($organization);

    expect($mail->hasApproximation)->toBeTrue()
        ->and(collect($mail->campaigns)->pluck('approximate', 'title')->all())
        ->toBe(['A Ringgit' => false, 'B Mixed' => true]);
});

it('names the organization in the subject', function () {
    $organization = Organization::factory()->make(['name' => 'Masjid Al Ayubi']);

    $mail = new MonthlyReport(
        organization: $organization,
        donationCount: 0,
        totalAmount: '0.00',
        campaigns: [],
        period: 'October 2026',
    );

    expect($mail->envelope()->subject)->toBe('Masjid Al Ayubi — Donation report, October 2026');
});

it('compares the total with the month before', function () {
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);
    $campaign = Campaign::factory()->create(['organization_id' => $organization->getKey()]);

    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'gross_amount' => 100, 'base_amount' => 100, 'created_at' => '2026-04-20 12:00:00']);
    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'gross_amount' => 125, 'base_amount' => 125, 'created_at' => '2026-05-20 12:00:00']);

    $mail = sendMayReportFor($organization);

    expect($mail->previousTotalAmount)->toBe('100.00')
        ->and($mail->changePercent)->toBe(25.0);
});

it('leaves the change empty when the month before had nothing to compare with', function () {
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);
    $campaign = Campaign::factory()->create(['organization_id' => $organization->getKey()]);
    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'gross_amount' => 125, 'base_amount' => 125, 'created_at' => '2026-05-20 12:00:00']);

    $mail = sendMayReportFor($organization);

    expect($mail->changePercent)->toBeNull();
});

it('splits one-time from recurring gifts and reports the average and largest gift', function () {
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);
    $campaign = Campaign::factory()->create(['organization_id' => $organization->getKey()]);

    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'gross_amount' => 200, 'base_amount' => 200, 'created_at' => '2026-05-03 12:00:00']);
    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'gross_amount' => 40, 'base_amount' => 40, 'type' => DonationType::Recurring, 'created_at' => '2026-05-04 12:00:00']);
    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'gross_amount' => 60, 'base_amount' => 60, 'type' => DonationType::Recurring, 'created_at' => '2026-05-05 12:00:00']);

    $mail = sendMayReportFor($organization);

    expect($mail->highlights)->toMatchArray([
        'one_time_count' => 1,
        'one_time_total' => '200.00',
        'recurring_count' => 2,
        'recurring_total' => '100.00',
        'average_amount' => '100.00',
        'largest_amount' => '200.00',
    ]);
});

it('counts first-time supporters apart from returning ones', function () {
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);
    $campaign = Campaign::factory()->create(['organization_id' => $organization->getKey()]);
    $otherCampaign = Campaign::factory()->create();

    $returning = Donor::factory()->create();
    $newHere = Donor::factory()->create();

    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'donor_id' => $returning->getKey(), 'created_at' => '2026-02-01 12:00:00']);
    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'donor_id' => $returning->getKey(), 'created_at' => '2026-05-10 12:00:00']);
    // Giving to another organization before does not make them a returning supporter here.
    Donation::factory()->create(['campaign_id' => $otherCampaign->getKey(), 'donor_id' => $newHere->getKey(), 'created_at' => '2026-01-01 12:00:00']);
    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'donor_id' => $newHere->getKey(), 'created_at' => '2026-05-11 12:00:00']);
    Donation::factory()->create(['campaign_id' => $campaign->getKey(), 'donor_id' => $newHere->getKey(), 'created_at' => '2026-05-12 12:00:00']);

    $mail = sendMayReportFor($organization);

    expect($mail->highlights)->toMatchArray([
        'new_donor_count' => 1,
        'returning_donor_count' => 1,
    ]);
});

it('reports monthly plans started, cancelled and past due', function () {
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);
    $campaign = Campaign::factory()->create(['organization_id' => $organization->getKey()]);

    Subscription::factory()->create(['campaign_id' => $campaign->getKey(), 'created_at' => '2026-05-02 12:00:00']);
    Subscription::factory()->create(['campaign_id' => $campaign->getKey(), 'created_at' => '2026-05-03 12:00:00']);
    Subscription::factory()->create(['campaign_id' => $campaign->getKey(), 'status' => SubscriptionStatus::Incomplete, 'created_at' => '2026-05-04 12:00:00']);
    Subscription::factory()->create(['campaign_id' => $campaign->getKey(), 'status' => SubscriptionStatus::Cancelled, 'created_at' => '2026-01-04 12:00:00', 'cancelled_at' => '2026-05-20 12:00:00']);
    Subscription::factory()->create(['campaign_id' => $campaign->getKey(), 'status' => SubscriptionStatus::PastDue, 'created_at' => '2026-01-05 12:00:00']);
    Subscription::factory()->create(['status' => SubscriptionStatus::PastDue]);

    $mail = sendMayReportFor($organization);

    expect($mail->highlights)->toMatchArray([
        'new_plan_count' => 2,
        'cancelled_plan_count' => 1,
        'past_due_plan_count' => 1,
    ]);
});

it('links to the report page for the month', function () {
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);

    $mail = sendMayReportFor($organization);

    expect($mail->reportUrl)->toEndWith('/reports/monthly-donations?month=2026-05');
});

it('does not mark the month sent when the organization has no admin to receive it', function () {
    Mail::fake();
    now()->setTestNow(now()->parse('2026-06-01 08:00:00'));

    $organization = Organization::factory()->create(['settings' => ['monthly_report' => true]]);

    Artisan::call('ihsan:send-monthly-report');

    Mail::assertNothingQueued();
    expect($organization->refresh()->settings['monthly_report_last_sent'] ?? null)->toBeNull();
});

it('renders the report with the note inside the card', function () {
    $organization = Organization::factory()->create(['name' => 'Masjid Al Ayubi']);

    $html = (new MonthlyReport(
        organization: $organization,
        donationCount: 3,
        totalAmount: '300.00',
        campaigns: [['title' => 'Dana', 'count' => 3, 'total' => '300.00', 'approximate' => true]],
        period: 'May 2026',
        hasApproximation: true,
        previousTotalAmount: '240.00',
        changePercent: 25.0,
        highlights: [
            'one_time_count' => 1, 'one_time_total' => '200.00',
            'recurring_count' => 2, 'recurring_total' => '100.00',
            'new_donor_count' => 1, 'returning_donor_count' => 2,
            'average_amount' => '100.00', 'largest_amount' => '200.00',
            'new_plan_count' => 2, 'cancelled_plan_count' => 1, 'past_due_plan_count' => 1,
        ],
        reportUrl: 'https://app.example.test/reports/monthly-donations?month=2026-05',
    ))->render();

    $cardEnd = strrpos($html, '</div>');

    expect($html)
        ->toContain('May 2026 report')
        ->toContain('25%')
        ->toContain('MYR 240.00')
        ->toContain('https://app.example.test/reports/monthly-donations?month=2026-05')
        ->and(strpos($html, 'converted to MYR'))->toBeLessThan($cardEnd);
});

it('opens the report page at the month in the link', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    Livewire::withQueryParams(['month' => '2026-05'])
        ->actingAs($user)
        ->test(MonthlyDonations::class)
        ->assertSet('selectedMonth', '2026-05')
        ->assertSet('dateFrom', '2026-05-01');
});
