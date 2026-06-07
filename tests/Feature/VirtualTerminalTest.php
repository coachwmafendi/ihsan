<?php

use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->actingAs($this->user);
});

test('virtual terminal page is accessible by org admin', function () {
    // TODO: use route() helper once VirtualTerminal page is registered
    $response = $this->get('/app/virtual-terminal');
    $response->assertOk();
});

test('virtual terminal page preloads supporter from query param', function () {
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);

    // TODO: use route() helper once VirtualTerminal page is registered
    $response = $this->get("/app/virtual-terminal?vt-supporter={$donor->public_id}");
    $response->assertOk();
    $response->assertSee('Ahmad Ali');
    $response->assertSee('ahmad@example.com');
});

test('one-time donation creates donation record for existing donor', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);

    // TODO: use route() helper once VirtualTerminal page is registered
    $response = $this->post('/app/virtual-terminal/actions/processDonation', [
        'campaign_id' => (string) $campaign->id,
        'frequency' => 'once',
        'amount' => '50.00',
        'first_name' => 'Ahmad',
        'last_name' => 'Ali',
        'email' => 'ahmad@example.com',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('donations', [
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'gross_amount' => '50.00',
    ]);
});
