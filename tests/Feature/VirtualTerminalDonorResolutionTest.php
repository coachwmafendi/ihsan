<?php

declare(strict_types=1);

use App\Actions\Stripe\ProcessVirtualTerminalDonation;
use App\Models\Donor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function resolveDonor(string $first, string $last, string $email): Donor
{
    $action = app(ProcessVirtualTerminalDonation::class);
    $method = new ReflectionMethod($action, 'resolveOrCreateDonor');

    return $method->invoke($action, $first, $last, $email);
}

it('reuses an existing donor by email instead of creating a duplicate', function () {
    $existing = Donor::factory()->create([
        'email' => 'donor@example.test',
        'first_name' => 'Old',
        'last_name' => 'Name',
    ]);

    $resolved = resolveDonor('New', 'Name', 'donor@example.test');

    expect($resolved->id)->toBe($existing->id)
        ->and($resolved->first_name)->toBe('New');

    expect(Donor::where('email', 'donor@example.test')->count())->toBe(1);
});

it('matches donor email case-insensitively', function () {
    $existing = Donor::factory()->create(['email' => 'mixed@example.test']);

    $resolved = resolveDonor('Some', 'One', 'MIXED@example.test');

    expect($resolved->id)->toBe($existing->id);
    expect(Donor::count())->toBe(1);
});

it('creates a donor when the email is new', function () {
    $resolved = resolveDonor('Fresh', 'Donor', 'fresh@example.test');

    expect($resolved->exists)->toBeTrue()
        ->and($resolved->email)->toBe('fresh@example.test');
});
