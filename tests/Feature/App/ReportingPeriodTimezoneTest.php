<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Livewire\App\Donations\DonationIndex;
use App\Livewire\App\Reports\MonthlyDonations;
use App\Livewire\App\Subscriptions\SubscriptionIndex;
use App\Livewire\App\Supporters\SupporterIndex;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

/**
 * Timestamps are stored in UTC and read in Malaysian time. A record created at
 * 07:04 MYT on 31 August is stored as 23:04 UTC on 30 August, so a filter that
 * measures the day in UTC drops it from "today" — which is exactly what the
 * dashboard did in production.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();

    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:41:00', 'Asia/Kuala_Lumpur'));

    $this->earlyToday = CarbonImmutable::parse('2026-08-31 07:04:45', 'Asia/Kuala_Lumpur')->utc();
    $this->lateYesterday = CarbonImmutable::parse('2026-08-30 23:30:00', 'Asia/Kuala_Lumpur')->utc();
});

it('resolves today on the organization clock, expressed in UTC', function () {
    [$from, $to] = ReportingPeriod::for($this->organization)->utc('today');

    expect($from->toDateTimeString())->toBe('2026-08-30 16:00:00')
        ->and($to->toDateTimeString())->toBe('2026-08-31 15:59:59');
});

it('lists an early-morning donation under today', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => $this->earlyToday,
    ]);

    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => $this->lateYesterday,
    ]);

    $rows = Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->set('period', 'today')
        ->instance()
        ->donations();

    expect($rows->total())->toBe(1);
});

it('lists an early-morning recurring plan under today', function () {
    Subscription::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => SubscriptionStatus::Active,
        'created_at' => $this->earlyToday,
    ]);

    Subscription::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => SubscriptionStatus::Active,
        'created_at' => $this->lateYesterday,
    ]);

    $rows = Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->set('period', 'today')
        ->instance()
        ->subscriptions();

    expect($rows->total())->toBe(1);
});

it('lists a supporter who first gave this morning under today', function () {
    $todaysDonor = Donor::factory()->create(['created_at' => $this->earlyToday]);
    $yesterdaysDonor = Donor::factory()->create(['created_at' => $this->lateYesterday]);

    foreach ([$todaysDonor, $yesterdaysDonor] as $donor) {
        Donation::factory()->for($this->campaign)->for($donor)->create([
            'status' => DonationStatus::Succeeded,
            'created_at' => $donor->created_at,
        ]);
    }

    $rows = Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->set('period', 'today')
        ->instance()
        ->donors();

    expect($rows->total())->toBe(1);
});

it('counts an early-morning donation in the month it was made locally', function () {
    // 1 Sep 07:00 MYT is still 31 Aug in UTC, so a UTC month boundary would
    // report this donation under August.
    $this->travelTo(CarbonImmutable::parse('2026-09-01 09:00:00', 'Asia/Kuala_Lumpur'));

    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 75.00,
        'base_amount' => 75.00,
        'created_at' => CarbonImmutable::parse('2026-09-01 07:00:00', 'Asia/Kuala_Lumpur')->utc(),
    ]);

    $component = Livewire::actingAs($this->user)->test(MonthlyDonations::class);

    expect($component->get('selectedMonth'))->toBe('2026-09')
        ->and($component->instance()->summary()['total_donations'])->toBe(1);
});

it('measures the day on the organization own clock', function () {
    // 07:04 MYT on 31 Aug is 06:04 in Jakarta, still the same day there; but
    // 00:30 MYT is 23:30 the previous day in Jakarta.
    $jakarta = Organization::factory()->create(['timezone' => 'Asia/Jakarta']);

    [$from, $to] = ReportingPeriod::for($jakarta)->utc('today');

    expect($from->toDateTimeString())->toBe('2026-08-30 17:00:00')
        ->and($to->toDateTimeString())->toBe('2026-08-31 16:59:59');
});

it('gives a Jakarta organization a different today from a Malaysian one', function () {
    $jakarta = Organization::factory()->create(['timezone' => 'Asia/Jakarta']);
    $jakartaCampaign = Campaign::factory()->for($jakarta)->create();
    $jakartaUser = User::factory()->create(['organization_id' => $jakarta->id]);

    // 00:30 on 31 Aug in Malaysia is still 30 Aug in Jakarta.
    $justAfterMalaysianMidnight = CarbonImmutable::parse('2026-08-31 00:30:00', 'Asia/Kuala_Lumpur')->utc();

    Donation::factory()->for($jakartaCampaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => $justAfterMalaysianMidnight,
    ]);

    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => $justAfterMalaysianMidnight,
    ]);

    $jakartaToday = Livewire::actingAs($jakartaUser)
        ->test(DonationIndex::class)
        ->set('period', 'today')
        ->instance()
        ->donations();

    $malaysianToday = Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->set('period', 'today')
        ->instance()
        ->donations();

    expect($jakartaToday->total())->toBe(0)
        ->and($malaysianToday->total())->toBe(1);
});

it('falls back to the platform default when the stored timezone is unusable', function () {
    $organization = Organization::factory()->create(['timezone' => 'Mars/Olympus_Mons']);

    expect($organization->reportingTimezone())->toBe(ReportingPeriod::DefaultTimezone);
});

it('names the clock the figures are read on', function () {
    expect(ReportingPeriod::for($this->organization)->label())->toBe('Kuala Lumpur (UTC+8)')
        ->and((new ReportingPeriod('Asia/Jakarta'))->label())->toBe('Jakarta (UTC+7)')
        ->and((new ReportingPeriod('Asia/Kolkata'))->label())->toBe('Kolkata (UTC+5:30)')
        ->and((new ReportingPeriod('UTC'))->label())->toBe('UTC');
});

it('lets an organization admin change the reporting timezone', function () {
    Livewire::actingAs($this->user)
        ->test(App\Livewire\App\Settings\Organization::class)
        ->assertSet('timezone', 'Asia/Kuala_Lumpur')
        ->set('timezone', 'Asia/Jakarta')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->organization->fresh()->reportingTimezone())->toBe('Asia/Jakarta');
});

it('rejects a timezone the server does not recognise', function () {
    Livewire::actingAs($this->user)
        ->test(App\Livewire\App\Settings\Organization::class)
        ->set('timezone', 'Mars/Olympus_Mons')
        ->call('save')
        ->assertHasErrors(['timezone']);

    expect($this->organization->fresh()->reportingTimezone())->toBe('Asia/Kuala_Lumpur');
});
