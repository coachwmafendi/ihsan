<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Livewire\App\Reports\MonthlyDonations;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
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
        ->assertSee('Monthly Donation Report')
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
        ->toContain('Monthly Donation Report')
        ->toContain('Qurban 2026')
        ->toContain('250.00')
        ->toContain('242.50')
        ->toContain('7.50');
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
