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

it('uses wire:navigate on internal sidebar links', function (string $path) {
    $response = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk();

    expect(preg_match(
        '/<a\s+href="'.preg_quote($path, '/').'"[\s\S]*?wire:navigate/',
        $response->getContent()
    ))->toBe(1, "Expected {$path} sidebar link to use wire:navigate.");
})->with([
    '/dashboard',
    '/campaigns',
    '/elements',
    '/donations',
    '/recurring-plans',
    '/supporters',
    '/audit-log',
    '/reports/monthly-donations',
    '/settings/organization',
    '/settings/payment',
    '/settings/account',
]);

it('does not use wire:navigate on external sidebar links', function () {
    $response = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk();

    $html = $response->getContent();

    preg_match_all('/<a\s+href="\/virtual-terminal"[^>]*>/', $html, $matches);

    expect($matches[0][0] ?? '')->not->toContain('wire:navigate');
});
