<?php

use App\Livewire\App\Supporters\SupporterIndex;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->donor = Donor::factory()->create();
    $this->firstDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 50.00,
        'base_amount' => null,
        'created_at' => Carbon::create(2026, 3, 15, 12, 0, 0),
    ]);
    $this->lastDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 75.00,
        'base_amount' => null,
        'created_at' => Carbon::create(2026, 6, 22, 12, 0, 0),
    ]);
});

it('renders supporters index with lifetime donated and first and last donation columns', function () {
    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertStatus(200);
    $response->assertSee('Lifetime Donated');
    $response->assertSee('First Donation');
    $response->assertSee('Last Donation');
    $response->assertSee($this->donor->name);
    $response->assertSee('MYR '.number_format(125.00, 2));
    $response->assertSee($this->firstDonation->created_at->format('M d, Y'));
    $response->assertSee($this->lastDonation->created_at->format('M d, Y'));
    $response->assertDontSee('Total Donated');
});

it('sorts supporters by first and last donation dates', function () {
    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->set('sortField', 'donations_min_created_at')
        ->assertSet('sortField', 'donations_min_created_at')
        ->set('sortField', 'donations_max_created_at')
        ->assertSet('sortField', 'donations_max_created_at');
});

it('sorts by last donation datetime', function () {
    $donorA = Donor::factory()->create();
    $donorB = Donor::factory()->create();

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $donorA->id,
        'gross_amount' => 10.00,
        'created_at' => now()->subDay()->setTime(23, 59),
    ]);

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $donorB->id,
        'gross_amount' => 10.00,
        'created_at' => now()->subDay()->setTime(1, 0),
    ]);

    $component = Livewire::actingAs($this->user)->test(SupporterIndex::class);
    $names = $component->instance()->donors->pluck('name')->toArray();

    expect($names)->toContain($donorA->name, $donorB->name, $this->donor->name)
        ->and(array_search($donorA->name, $names))->toBeLessThan(array_search($donorB->name, $names));
});

it('defaults to sorting by last donation date', function () {
    $component = Livewire::actingAs($this->user)->test(SupporterIndex::class);

    expect($component->instance()->sortField)->toBe('donations_max_created_at')
        ->and($component->instance()->sortDirection)->toBe('desc');
});

it('shows approximate MYR lifetime amount for foreign currency donations', function () {
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 100.00,
        'currency' => 'usd',
        'base_amount' => 450.00,
        'base_currency' => 'myr',
    ]);

    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertSee('≈ MYR 575.00');
});

it('preserves donor name casing', function () {
    $this->donor->update(['name' => 'AHMAD BIN ABU']);

    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertSee('AHMAD BIN ABU');
    $response->assertDontSee('Ahmad Bin Abu');
});

it('renders the name column without wrapping', function () {
    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertOk()
        ->assertSeeHtml('class="whitespace-nowrap min-w-[200px] px-5 py-4"')
        ->assertSee($this->donor->name);
});

it('only shows the exact amount tooltip for non-MYR donations', function () {
    $myrDonor = Donor::factory()->create(['name' => 'MYR Only Donor']);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $myrDonor->id,
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'base_amount' => null,
    ]);

    $usdDonor = Donor::factory()->create(['name' => 'USD Donor']);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $usdDonor->id,
        'gross_amount' => 25.00,
        'currency' => 'usd',
        'base_amount' => 110.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertOk()
        ->assertSee('USD 25.00')
        ->assertSee('aria-label="USD 25.00"', false)
        ->assertDontSee('aria-label="MYR 100.00"', false)
        ->assertSee('MYR Only Donor')
        ->assertSee('USD Donor');
});

it('shows approximate myr lifetime total for foreign donations without base amount', function () {
    $donor = Donor::factory()->create();

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $donor->id,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => null,
    ]);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $donor->id,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.supporters.index'))
        ->assertOk()
        ->assertSee('≈ MYR 200.00');
});

it('scopes donor aggregates to the current organization only', function () {
    $otherOrganization = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 100.00,
        'created_at' => now()->subDays(10),
    ]);

    Donation::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 999.00,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)->test(SupporterIndex::class);
    $donor = $component->instance()->donors->firstWhere('id', $this->donor->id);

    expect($donor->donations_count)->toBe(3)
        ->and((float) $donor->lifetime_report_amount)->toBe(225.00)
        ->and(substr((string) $donor->donations_max_created_at, 0, 10))->toBe($this->lastDonation->created_at->format('Y-m-d'));
});

it('redirects guests to login', function () {
    $this->get(route('app.supporters.index'))->assertRedirect('/login');
});

it('paginates supporters based on per page selection', function () {
    $donors = Donor::factory()->count(29)->create();

    foreach ($donors as $donor) {
        Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $donor->id,
        ]);
    }

    $component = Livewire::actingAs($this->user)->test(SupporterIndex::class);

    expect($component->instance()->donors)->toHaveCount(25);

    $component->set('perPage', 10);

    $paginated = $component->instance()->donors;

    expect($paginated)->toHaveCount(10)
        ->and($paginated->total())->toBe(30);

    $component->set('perPage', 50);

    expect($component->instance()->donors)->toHaveCount(30);
});
