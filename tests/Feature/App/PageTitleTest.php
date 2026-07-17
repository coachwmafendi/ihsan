<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->for($this->organization)->create();
    Campaign::factory()->for($this->organization)->count(2)->create();
});

it('formats app page titles as {Title} - {Organization Name}', function (string $path, string $expectedTitle) {
    $this->actingAs($this->user)
        ->get($path)
        ->assertOk()
        ->assertSee("<title>{$expectedTitle} - {$this->organization->name}</title>", false);
})->with([
    ['/app/dashboard', 'Dashboard'],
    ['/app/campaigns', 'Campaigns'],
    ['/app/elements', 'Elements'],
    ['/app/donations', 'Donations'],
    ['/app/recurring-plans', 'Recurring Plans'],
    ['/app/supporters', 'Supporters'],
]);
