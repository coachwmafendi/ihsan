<?php

use App\Enums\OrganizationStatus;
use App\Models\Organization;

test('approved_at is backfilled when status transitions to active without it set', function () {
    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::Pending,
        'approved_at' => null,
    ]);

    $organization->update(['status' => OrganizationStatus::Active]);

    expect($organization->fresh()->approved_at)->not->toBeNull();
});

test('approved_at is not overwritten when already set', function () {
    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::Pending,
        'approved_at' => null,
    ]);

    $organization->update([
        'status' => OrganizationStatus::Active,
        'approved_at' => $approvedAt = now()->subDays(3),
    ]);

    expect($organization->fresh()->approved_at->timestamp)->toBe($approvedAt->timestamp);
});

test('approved_at stays null while status remains pending', function () {
    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::Pending,
        'approved_at' => null,
    ]);

    $organization->update(['name' => 'Updated Name']);

    expect($organization->fresh()->approved_at)->toBeNull();
});
