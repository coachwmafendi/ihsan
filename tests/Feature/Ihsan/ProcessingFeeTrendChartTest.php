<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Filament\Widgets\ProcessingFeeTrendChart;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Carbon\CarbonImmutable;

/**
 * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
 */
function trendChartData(): array
{
    $method = new ReflectionMethod(ProcessingFeeTrendChart::class, 'getData');
    $method->setAccessible(true);

    return $method->invoke(new ProcessingFeeTrendChart);
}

function seedFee(float $amount, string $status, CarbonImmutable $createdAt): void
{
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donation = Donation::factory()->for($campaign)->for(Donor::factory())->create([
        'status' => DonationStatus::Succeeded,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => $donation->id,
        'organization_id' => $org->id,
        'fee_amount' => $amount,
        'status' => $status,
        'created_at' => $createdAt,
    ]);
}

it('plots fees whatever status they carry', function () {
    // Filtering to 'paid' drew a flat zero line while fees were being earned:
    // upfront collection writes 'collected', invoicing writes 'pending'.
    $this->travelTo(CarbonImmutable::parse('2026-08-31 12:00:00', 'Asia/Kuala_Lumpur'));

    seedFee(5.00, 'collected', CarbonImmutable::parse('2026-08-10 09:00:00', 'Asia/Kuala_Lumpur')->utc());
    seedFee(3.00, 'pending', CarbonImmutable::parse('2026-08-12 09:00:00', 'Asia/Kuala_Lumpur')->utc());
    seedFee(2.00, 'paid', CarbonImmutable::parse('2026-08-14 09:00:00', 'Asia/Kuala_Lumpur')->utc());

    $data = trendChartData();
    $currentMonth = array_key_last($data['labels']);

    expect($data['labels'][$currentMonth])->toBe('Aug 2026')
        ->and($data['datasets'][0]['data'][$currentMonth])->toBe(10.0);
});

it('buckets a fee into the month it was recorded locally', function () {
    // 07:00 MYT on 1 August is 23:00 UTC on 31 July, so a UTC bucket would
    // report it as July's fee.
    $this->travelTo(CarbonImmutable::parse('2026-08-31 12:00:00', 'Asia/Kuala_Lumpur'));

    seedFee(7.00, 'collected', CarbonImmutable::parse('2026-08-01 07:00:00', 'Asia/Kuala_Lumpur')->utc());

    $data = trendChartData();
    $august = array_search('Aug 2026', $data['labels'], true);
    $july = array_search('Jul 2026', $data['labels'], true);

    expect($data['datasets'][0]['data'][$august])->toBe(7.0)
        ->and($data['datasets'][0]['data'][$july])->toBe(0.0);
});
