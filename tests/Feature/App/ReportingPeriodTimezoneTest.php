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

it('resolves today as the Malaysian day, expressed in UTC', function () {
    [$from, $to] = ReportingPeriod::utc('today');

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
