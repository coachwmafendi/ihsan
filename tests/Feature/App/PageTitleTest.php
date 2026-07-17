<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create([
        'name' => 'Acme Foundation',
    ]);
    $this->user = User::factory()->for($this->organization)->create();
    Campaign::factory()->for($this->organization)->count(2)->create();
});

it('formats app page titles as {Title} - {Organization Name}', function (string $path, string $expectedTitle) {
    $this->actingAs($this->user)
        ->get($path)
        ->assertOk()
        ->assertSee("<title>{$expectedTitle} - {$this->organization->name}</title>", false);
})->with([
    ['https://app.example.test/dashboard', 'Dashboard'],
    ['https://app.example.test/campaigns', 'Campaigns'],
    ['https://app.example.test/elements', 'Elements'],
    ['https://app.example.test/donations', 'Donations'],
    ['https://app.example.test/recurring-plans', 'Recurring Plans'],
    ['https://app.example.test/supporters', 'Supporters'],
    ['https://app.example.test/audit-log', 'Audit Log'],
    ['https://app.example.test/billing', 'Billing'],
    ['https://app.example.test/notifications', 'Notifications'],
    ['https://app.example.test/settings/account', 'Settings — Account'],
    ['https://app.example.test/settings/organization', 'Settings — Organization'],
    ['https://app.example.test/settings/payment', 'Settings — Payment Processors'],
    ['https://app.example.test/settings/notifications', 'Settings — Notifications'],
    ['https://app.example.test/settings/donor-portal', 'Settings — Donor Portal'],
    ['https://app.example.test/settings/allow-domains', 'Settings — Allowed Domains'],
    ['https://app.example.test/settings/tracking', 'Settings — Tracking'],
    ['https://app.example.test/settings/installation', 'Settings — Installation'],
]);

it('formats supporter detail page title as {Name} - Supporter - {Organization Name}', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    $donor = Donor::factory()->create(['name' => 'John Doe']);
    Donation::factory()->for($campaign)->for($donor)->create();

    $this->actingAs($this->user)
        ->get("https://app.example.test/supporters/{$donor->public_id}")
        ->assertOk()
        ->assertSee('<title>John Doe - Supporter - Acme Foundation</title>', false);
});
