<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Filament\App\Resources\Donations\DonationResource;
use App\Filament\App\Resources\Donors\DonorResource;
use App\Filament\Exports\DonationExporter;
use App\Filament\Exports\DonorExporter;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);
    $this->actingAs($this->user);

    $this->otherOrganization = Organization::factory()->create();
});

it('has donation export columns', function () {
    $columns = DonationExporter::getColumns();
    $names = array_map(fn ($c) => $c->getName(), $columns);

    expect($columns)->not->toBeEmpty();
    expect($names)->toContain('invoice_number', 'created_at', 'gross_amount', 'status', 'donor.name', 'campaign.title');
});

it('has donor export columns', function () {
    $columns = DonorExporter::getColumns();
    $names = array_map(fn ($c) => $c->getName(), $columns);

    expect($columns)->not->toBeEmpty();
    expect($names)->toContain('name', 'email', 'lifetime_total_myr', 'address_city', 'country');
});

it('exports donation data with correct formatting', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    $donor = Donor::factory()->create(['name' => 'Ahmad Ali', 'email' => 'ahmad@example.com']);
    $donation = Donation::factory()
        ->for($campaign)
        ->for($donor)
        ->create([
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::OneTime,
            'gross_amount' => 150.00,
            'currency' => 'myr',
            'stripe_fee' => 4.50,
            'processing_fee' => 2.50,
            'net_amount' => 143.00,
        ]);

    $columnMap = collect(DonationExporter::getColumns())
        ->mapWithKeys(fn ($c) => [$c->getName() => $c->getLabel()])
        ->all();

    $exportModel = new Export;
    $exporter = new DonationExporter($exportModel, $columnMap, []);
    $data = $exporter($donation);
    $map = array_keys($columnMap);

    expect($data[array_search('created_at', $map)])->toBe($donation->created_at->format('Y-m-d H:i:s'));
    expect($data[array_search('gross_amount', $map)])->toBe('150.00');
    expect($data[array_search('currency', $map)])->toBe('MYR');
    expect($data[array_search('status', $map)])->toBe('Succeeded');
    expect($data[array_search('donor.name', $map)])->toBe('Ahmad Ali');
    expect($data[array_search('campaign.title', $map)])->toBe($campaign->title);
});

it('exports donor data with correct formatting', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    $donor = Donor::factory()->create([
        'name' => 'Siti Aminah',
        'email' => 'siti@example.com',
        'address_city' => 'Kuala Lumpur',
        'country' => 'my',
    ]);
    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100,
        'base_amount' => 100,
        'currency' => 'myr',
    ]);

    $columnMap = collect(DonorExporter::getColumns())
        ->mapWithKeys(fn ($c) => [$c->getName() => $c->getLabel()])
        ->all();

    $exportModel = new Export;
    $exporter = new DonorExporter($exportModel, $columnMap, []);

    // DonorsTable adds aggregates via modifyQueryUsing, so emulate that
    $donor = Donor::query()
        ->withAggregate('donations', 'created_at', 'min')
        ->withAggregate('donations', 'created_at', 'max')
        ->selectSub(
            Donation::whereColumn('donor_id', 'donors.id')
                ->selectRaw('COALESCE(SUM(COALESCE(base_amount, gross_amount)), 0)'),
            'lifetime_total_myr'
        )
        ->whereKey($donor->id)
        ->first();

    $data = $exporter($donor);
    $map = array_keys($columnMap);

    expect($data[array_search('name', $map)])->toBe('Siti Aminah');
    expect($data[array_search('email', $map)])->toBe('siti@example.com');
    expect($data[array_search('address_city', $map)])->toBe('Kuala Lumpur');
    expect($data[array_search('country', $map)])->toBe('MY');
    expect($data[array_search('lifetime_total_myr', $map)])->toBe('MYR 100.00');
});

it('scopes donation query to authenticated organization', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    $otherCampaign = Campaign::factory()->for($this->otherOrganization)->create();

    Donation::factory()->for($campaign)->create();
    Donation::factory()->for($otherCampaign)->create();

    $query = DonationResource::getEloquentQuery();

    expect($query->count())->toBe(1);
    expect($query->first()->campaign->organization_id)->toBe($this->organization->id);
});

it('scopes donor query to authenticated organization', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    $otherCampaign = Campaign::factory()->for($this->otherOrganization)->create();

    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create();
    Donation::factory()->for($otherCampaign)->for($otherDonor)->create();

    $query = DonorResource::getEloquentQuery();

    expect($query->count())->toBe(1);
    expect($query->first()->id)->toBe($donor->id);
});
