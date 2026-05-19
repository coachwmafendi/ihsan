<?php

use App\Models\Donor;

it('logs in with valid magic token', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'valid-token-123',
        'magic_token_expires_at' => now()->addHours(24),
    ]);

    $this->get(route('donorportal.magic-login', ['token' => 'valid-token-123']))
        ->assertRedirect(route('donorportal.donations'));

    $this->assertEquals(session('donor_id'), $donor->getKey());
});

it('rejects expired magic token', function () {
    Donor::factory()->create([
        'magic_token' => 'expired-token',
        'magic_token_expires_at' => now()->subHour(),
    ]);

    $this->get(route('donorportal.magic-login', ['token' => 'expired-token']))
        ->assertRedirect(route('donorportal.login'));
});

it('requires valid session for donor portal pages', function () {
    $this->get(route('donorportal.donations'))->assertRedirect(route('donorportal.login'));
    $this->get(route('donorportal.subscriptions'))->assertRedirect(route('donorportal.login'));
});

it('shows donation history for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations'))
        ->assertOk()
        ->assertSee('Donations')
        ->assertSee($donor->name);
});

it('shows subscriptions for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.subscriptions'))
        ->assertOk()
        ->assertSee('Subscriptions');
});

it('logs out donor and redirects to login', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->post(route('donorportal.logout'))
        ->assertRedirect(route('donorportal.login'));

    $this->assertNull(session('donor_id'));
});
