<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Livewire\App\Reports\MonthlyDonations;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    // The report opens on the current Malaysian month, so a fixture created at
    // "now" has to land inside it. Left free-running, these tests fail every
    // month between midnight and 8am on the first.
    $this->travelTo(CarbonImmutable::parse('2026-08-15 12:00:00', 'Asia/Kuala_Lumpur'));

    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->for($this->organization)->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create(['title' => 'Qurban 2026']);
    $this->donor = Donor::factory()->create();
});

it('redirects guests to login', function () {
    get('https://app.example.test/reports/monthly-donations')
        ->assertRedirect(route('login'));
});

it('renders for authenticated ngo admin', function () {
    actingAs($this->user)
        ->get('https://app.example.test/reports/monthly-donations')
        ->assertOk()
        ->assertSee('Report')
        ->assertSee('Review donation collections for your organization');
});

it('shows correct summary cards when donations exist', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'processing_fee' => 2.00,
        'stripe_fee' => 1.00,
        'net_amount' => 97.00,
        'created_at' => now(),
    ]);

    actingAs($this->user)
        ->get('https://app.example.test/reports/monthly-donations')
        ->assertOk()
        ->assertSee('Total Gross')
        ->assertSee('MYR 100.00')
        ->assertSee('Processing Fee')
        ->assertSee('MYR 3.00')
        ->assertSee('Net Received')
        ->assertSee('MYR 97.00')
        ->assertSee('Total Donations')
        ->assertSee('1')
        ->assertSee('Unique Donors')
        ->assertSee('Qurban 2026');
});

it('surfaces donor-covered fees so net reconciles with gross and fees', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'donor_fee_covered' => 3.00,
        'processing_fee' => 2.00,
        'stripe_fee' => 1.00,
        'net_amount' => 100.00, // 100 + 3 donor-covered - 2 - 1
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(MonthlyDonations::class)
        ->assertSet('summary.total_gross', 100.00)
        ->assertSet('summary.donor_covered_fees', 3.00)
        ->assertSet('summary.processing_fee', 3.00)
        ->assertSet('summary.net_received', 100.00)
        ->assertSee('Donor-covered Fees');

    // Reconciles: gross + donor-covered - fee = net
});

it('includes chip fee in the processing fee total', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'chip_fee' => 4.00,
        'processing_fee' => 2.00,
        'stripe_fee' => 0.00,
        'net_amount' => 94.00, // 100 - 4 chip - 2 processing
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(MonthlyDonations::class)
        ->assertSet('summary.processing_fee', 6.00)
        ->assertSet('summary.net_received', 94.00);
});

it('converts donor-covered fees to myr for non-myr donations', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'currency' => 'usd',
        'gross_amount' => 100.00,     // usd
        'base_amount' => 450.00,      // myr @ 4.5
        'exchange_rate' => 4.5,
        'donor_fee_covered' => 3.00,  // usd -> 13.50 myr
        'processing_fee' => 5.00,
        'stripe_fee' => 4.00,
        'net_amount' => 454.50,       // 450 + 13.50 - 5 - 4
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(MonthlyDonations::class)
        ->assertSet('summary.total_gross', 450.00)
        ->assertSet('summary.donor_covered_fees', 13.50)
        ->assertSet('summary.net_received', 454.50);
});

it('does not show donations from other organizations', function () {
    $otherOrganization = Organization::factory()->stripeConnected()->create();
    $otherCampaign = Campaign::factory()->for($otherOrganization)->create(['title' => 'Other Org Campaign']);

    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'processing_fee' => 2.00,
        'stripe_fee' => 1.00,
        'net_amount' => 97.00,
        'created_at' => now(),
    ]);

    Donation::factory()->for($otherCampaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 999.00,
        'base_amount' => 999.00,
        'processing_fee' => 10.00,
        'stripe_fee' => 5.00,
        'net_amount' => 984.00,
        'created_at' => now(),
    ]);

    actingAs($this->user)
        ->get('https://app.example.test/reports/monthly-donations')
        ->assertOk()
        ->assertSee('MYR 100.00')
        ->assertSee('Qurban 2026')
        ->assertDontSee('Other Org Campaign')
        ->assertDontSee('999.00');
});

it('lists months from the first donation to the current month', function () {
    $firstDonationMonth = now()->subMonths(6)->startOfMonth();

    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'processing_fee' => 2.00,
        'stripe_fee' => 1.00,
        'net_amount' => 97.00,
        'created_at' => $firstDonationMonth->day(15),
    ]);

    Livewire::actingAs($this->user)
        ->test(MonthlyDonations::class)
        ->assertSee($firstDonationMonth->format('F Y'))
        ->assertSee(now()->format('F Y'));
});

it('updates summary and breakdown for custom date range', function () {
    $previousMonth = now()->subMonth();
    $previousCampaign = Campaign::factory()->for($this->organization)->create(['title' => 'Ramadan Fund']);

    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'processing_fee' => 2.00,
        'stripe_fee' => 1.00,
        'net_amount' => 97.00,
        'created_at' => now(),
    ]);

    Donation::factory()->for($previousCampaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'processing_fee' => 1.00,
        'stripe_fee' => 0.50,
        'net_amount' => 48.50,
        'created_at' => $previousMonth->day(15),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(MonthlyDonations::class)
        ->set('customRange', true)
        ->set('dateFrom', $previousMonth->startOfMonth()->toDateString())
        ->set('dateTo', $previousMonth->endOfMonth()->toDateString());

    $component->assertSet('summary.total_gross', 50.00)
        ->assertSet('summary.processing_fee', 1.50)
        ->assertSet('summary.net_received', 48.50)
        ->assertSet('summary.total_donations', 1)
        ->assertSee('Ramadan Fund')
        ->assertDontSee('MYR 100.00');
});

it('downloads a csv monthly donation report for an organization', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'gross_amount' => 250.00,
        'base_amount' => 250.00,
        'stripe_fee' => 4.50,
        'processing_fee' => 3.00,
        'net_amount' => 242.50,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->startOfMonth()->addDay(),
    ]);

    $periodSlug = now()->startOfMonth()->toDateString().'-to-'.now()->endOfMonth()->toDateString();
    $expectedFilename = "ihsan-{$this->organization->public_id}-".
        Str::limit(Str::slug($this->organization->name, '-'), 50, '').
        "-monthly-donations-{$periodSlug}.csv";

    $response = actingAs($this->user)
        ->get(route('app.reports.monthly-donations.download', [
            'format' => 'csv',
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)
        ->toContain($this->organization->name)
        ->toContain($this->organization->public_id)
        ->toContain('Report')
        ->toContain('Qurban 2026')
        ->toContain('250.00')
        ->toContain('242.50')
        ->toContain('7.50');
});

it('includes donor-covered and chip fees in the csv download so it reconciles', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 200.00,
        'base_amount' => 200.00,
        'donor_fee_covered' => 6.00,
        'chip_fee' => 3.00,
        'processing_fee' => 2.00,
        'stripe_fee' => 1.00,
        'net_amount' => 200.00, // 200 + 6 donor-covered - 3 - 2 - 1
        'created_at' => now()->startOfMonth()->addDay(),
    ]);

    $response = actingAs($this->user)
        ->get(route('app.reports.monthly-donations.download', ['format' => 'csv']));

    $response->assertOk();

    expect($response->streamedContent())
        ->toContain('"Donor-covered fees",6.00')
        ->toContain('"Processing fee",6.00') // 3 chip + 2 processing + 1 stripe
        ->toContain('"Net received",200.00');
});

it('downloads a pdf monthly donation report for an organization', function () {
    Donation::factory()->for($this->campaign)->for($this->donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'stripe_fee' => 2.00,
        'processing_fee' => 1.50,
        'net_amount' => 96.50,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subMonth()->day(15),
    ]);

    $lastMonth = now()->subMonth();
    $periodSlug = $lastMonth->startOfMonth()->toDateString().'-to-'.$lastMonth->endOfMonth()->toDateString();
    $expectedFilename = "ihsan-{$this->organization->public_id}-".
        Str::limit(Str::slug($this->organization->name, '-'), 50, '').
        "-monthly-donations-{$periodSlug}.pdf";

    $response = actingAs($this->user)
        ->get(route('app.reports.monthly-donations.download', [
            'format' => 'pdf',
            'dateFrom' => $lastMonth->startOfMonth()->toDateString(),
            'dateTo' => $lastMonth->endOfMonth()->toDateString(),
        ]));

    $response->assertOk();
    $response->assertDownload($expectedFilename);

    $content = $response->streamedContent();
    expect($content)->toContain('%PDF');
});

it('returns 404 for unknown download format', function () {
    actingAs($this->user)
        ->get(route('app.reports.monthly-donations.download', ['format' => 'xlsx']))
        ->assertNotFound();
});

it('denies download to users without an organization', function () {
    $user = User::factory()->create(['organization_id' => null]);

    actingAs($user)
        ->get(route('app.reports.monthly-donations.download', ['format' => 'csv']))
        ->assertForbidden();
});
