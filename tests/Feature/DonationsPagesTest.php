<?php

use App\Livewire\App\Donations\DonationShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
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
    $this->donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 150.00,
        'net_amount' => 145.50,
        'status' => 'succeeded',
    ]);
});

it('renders the donations index page', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.index'));

    $response->assertStatus(200);
    $response->assertSee('Donations');
    $response->assertSee($this->donor->name);
    $response->assertSee($this->campaign->title);
});

it('renders the donation show page', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertStatus(200)
        ->assertSee($this->donation->public_id)
        ->assertSee($this->donor->name)
        ->assertSee($this->campaign->title);
});

it('filters donations by period', function () {
    $oldDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'created_at' => now()->subMonths(2),
        'status' => 'succeeded',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['period' => 'this_month']));

    $response->assertStatus(200);
    $response->assertSee($this->donation->public_id);
    $response->assertDontSee($oldDonation->public_id);
});

it('filters donations by status', function () {
    $failedDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => 'failed',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['statusFilter' => 'succeeded']));

    $response->assertStatus(200);
    $response->assertSee($this->donation->public_id);
    $response->assertDontSee($failedDonation->public_id);
});

it('searches donations by donor name', function () {
    $otherDonor = Donor::factory()->create(['name' => 'Zara Unique']);
    $otherDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $otherDonor->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['search' => $otherDonor->name]));

    $response->assertStatus(200);
    $response->assertSee($otherDonation->public_id);
    $response->assertDontSee($this->donation->public_id);
});

it('displays donation detail sections', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Donation amount')
        ->assertSee('Payment & fees')
        ->assertSee('Personal information')
        ->assertSee('Source')
        ->assertSee('Insights')
        ->assertSee('Receipts')
        ->assertSee($this->donor->name)
        ->assertSee($this->campaign->title);
});

it('shows refund modal and validates reason', function () {
    $this->donation->update(['stripe_charge_id' => 'ch_test_123']);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->call('openRefundModal')
        ->assertSet('showRefundModal', true)
        ->call('confirmRefund')
        ->assertHasErrors(['refundReason' => 'required'])
        ->set('refundReason', 'duplicate')
        ->assertSet('refundReason', 'duplicate');
});

it('shows approximate myr total for foreign donations without base amount', function () {
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'currency' => 'usd',
        'gross_amount' => 150.00,
        'base_amount' => null,
        'status' => 'succeeded',
    ]);

    $response = $this->actingAs($this->user)->get(route('app.donations.index'));

    $response->assertOk()
        ->assertSee('≈ MYR')
        ->assertSee('USD 150.00');
});

it('renders date and donation columns without wrapping', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.index'));

    $response->assertOk()
        ->assertSeeHtml('class="whitespace-nowrap min-w-[180px] px-5 py-4 text-sm text-slate-500"')
        ->assertSeeHtml('class="whitespace-nowrap min-w-[180px] px-5 py-4">');
});

it('redirects guests to login', function () {
    $this->get(route('app.donations.index'))->assertRedirect('/login');
    $this->get(route('app.donations.show', $this->donation))->assertRedirect('/login');
});

it('links the source element to the element edit page', function () {
    $element = Element::factory()->create([
        'organization_id' => $this->organization->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $this->donation->update([
        'utm_params' => [
            'source' => 'element',
            'element_token' => $element->token,
            'element_type' => 'button',
            'element_name' => $element->name,
        ],
    ]);
    $this->donation->refresh();

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee($element->name)
        ->assertSeeHtml('href="'.e(route('app.elements.edit', $element)).'"');
});
