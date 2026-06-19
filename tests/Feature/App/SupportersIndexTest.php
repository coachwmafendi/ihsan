<?php

use App\Livewire\App\Supporters\SupporterIndex;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
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
        'created_at' => now()->subMonths(3),
    ]);
    $this->lastDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 75.00,
        'base_amount' => null,
        'created_at' => now()->subDays(5),
    ]);
});

it('renders supporters index with lifetime donated and first and last donation columns', function () {
    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertStatus(200);
    $response->assertSee('Lifetime donated');
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

it('displays donor names in title case', function () {
    $this->donor->update(['name' => 'AHMAD BIN ABU']);

    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertSee('Ahmad Bin Abu');
    $response->assertDontSee('AHMAD BIN ABU');
});

it('renders the name column without wrapping', function () {
    $response = $this->actingAs($this->user)->get(route('app.supporters.index'));

    $response->assertOk()
        ->assertSeeHtml('class="whitespace-nowrap min-w-[200px] px-5 py-4"')
        ->assertSee($this->donor->name);
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
