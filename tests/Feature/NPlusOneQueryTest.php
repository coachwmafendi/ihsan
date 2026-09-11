<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
});

it('does not trigger N+1 queries on the revenue page', function () {
    $organizations = Organization::factory(3)->create();
    foreach ($organizations as $organization) {
        $campaign = Campaign::factory()->for($organization)->create();
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::OneTime,
            'gross_amount' => 100.00,
        ]);
        ProcessingFee::factory()->create([
            'donation_id' => $donation->id,
            'organization_id' => $organization->id,
            'status' => 'transferred',
            'fee_amount' => 3.00,
        ]);
    }

    $queryCount = countQueries(function () {
        $this->get(route('filament.admin.pages.revenue'))->assertOk();
    });

    // Uses a single aggregated query instead of per-organization loops
    expect($queryCount)->toBeLessThanOrEqual(11);
});

it('does not trigger N+1 queries on the platform overview page', function () {
    $organizations = Organization::factory(3)->create();
    foreach ($organizations as $organization) {
        $campaign = Campaign::factory()->for($organization)->create();
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::OneTime,
            'gross_amount' => 100.00,
        ]);
        ProcessingFee::factory()->create([
            'donation_id' => $donation->id,
            'organization_id' => $organization->id,
            'status' => 'transferred',
            'fee_amount' => 3.00,
        ]);
    }

    $queryCount = countQueries(function () {
        $this->get(route('filament.admin.pages.platform-overview'))->assertOk();
    });

    // Includes the two constant eager-load queries (campaigns, organizations)
    // that back the recent donations list, which only run when donations exist,
    // and one aggregate for the weekly wallet share. The number that matters is
    // that it does not move with the number of organisations: measured at 47
    // for both 3 and 9 of them.
    expect($queryCount)->toBeLessThanOrEqual(47);
});

function countQueries(callable $callback): int
{
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries) {
        $queries[] = $query->sql;
    });
    $callback();

    return count($queries);
}
