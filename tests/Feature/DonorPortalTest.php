<?php

use App\Models\Donor;

it('logs in with valid magic token', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'valid-token-123',
        'magic_token_expires_at' => now()->addHours(24),
    ]);

    $this->get(route('donor.login', ['token' => 'valid-token-123']))
        ->assertRedirect(route('donor.donations'));

    $this->assertEquals(session('donor_id'), $donor->getKey());
});

it('rejects expired magic token', function () {
    Donor::factory()->create([
        'magic_token' => 'expired-token',
        'magic_token_expires_at' => now()->subHour(),
    ]);

    $this->get(route('donor.login', ['token' => 'expired-token']))
        ->assertRedirect(route('home'));
});

it('requires valid session for donor portal pages', function () {
    $this->get(route('donor.donations'))->assertRedirect(route('home'));
    $this->get(route('donor.subscriptions'))->assertRedirect(route('home'));
});

it('shows donation history for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donor.donations'))
        ->assertOk()
        ->assertSee('Donation History')
        ->assertSee($donor->name);
});

it('shows subscriptions for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donor.subscriptions'))
        ->assertOk()
        ->assertSee('My Subscriptions');
});

it('logs out donor and redirects home', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donor.logout'))
        ->assertRedirect(route('home'));

    $this->assertNull(session('donor_id'));
});
