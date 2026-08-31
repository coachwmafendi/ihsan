<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Filament\Pages\FraudPrevention;
use App\Filament\Pages\PlatformOverview;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RevenueReportService;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

/**
 * The admin panel reads the same UTC timestamps in Malaysian time. A donation
 * at 07:04 MYT on 31 August is stored as 23:04 UTC on 30 August, so a UTC day
 * boundary files it under yesterday.
 */
beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create();

    $this->travelTo(CarbonImmutable::parse('2026-08-31 08:41:00', 'Asia/Kuala_Lumpur'));
});

it('resolves the revenue report period in Malaysian time', function () {
    $service = app(RevenueReportService::class);

    // The label keeps the local calendar date the operator picked.
    expect($service->dateRange('today'))->toBe(['2026-08-31', '2026-08-31']);

    [$from, $to] = $service->queryRange('today');

    expect($from->toDateTimeString())->toBe('2026-08-30 16:00:00')
        ->and($to->toDateTimeString())->toBe('2026-08-31 15:59:59');
});

it('counts an early-morning recurring charge as today on the platform overview', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($this->campaign)->for($donor)->create();

    Donation::factory()->for($this->campaign)->for($donor)->create([
        'subscription_id' => $subscription->id,
        'status' => DonationStatus::Succeeded,
        'created_at' => CarbonImmutable::parse('2026-08-31 07:04:45', 'Asia/Kuala_Lumpur')->utc(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(PlatformOverview::class)
        ->assertSet('recurringHealthSuccessToday', 1);
});

it('counts an early-morning donation in the fraud page period', function () {
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => CarbonImmutable::parse('2026-08-31 07:04:45', 'Asia/Kuala_Lumpur')->utc(),
    ]);

    // 23:30 MYT yesterday shares the UTC day with the donation above, so a UTC
    // boundary would have counted both.
    Donation::factory()->for($this->campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
        'created_at' => CarbonImmutable::parse('2026-08-30 23:30:00', 'Asia/Kuala_Lumpur')->utc(),
    ]);

    $range = (new ReflectionMethod(FraudPrevention::class, 'dateRange'));
    $range->setAccessible(true);

    $page = new FraudPrevention;
    $page->period = 'today';

    [$from, $to] = $range->invoke($page);

    expect(Donation::query()->whereBetween('created_at', [$from, $to])->count())->toBe(1);
});
