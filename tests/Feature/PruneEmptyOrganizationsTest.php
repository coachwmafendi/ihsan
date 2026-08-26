<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Soft-delete an organization and backdate its deleted_at so it looks like it
 * was trashed long enough ago to be eligible for pruning.
 */
function trashOrganization(Organization $org, int $daysAgo = 60): Organization
{
    $org->delete();

    Organization::withTrashed()
        ->whereKey($org->getKey())
        ->update(['deleted_at' => now()->subDays($daysAgo)]);

    return $org;
}

it('force deletes empty organizations trashed beyond the retention window', function () {
    $org = trashOrganization(Organization::factory()->withoutStripe()->create());

    test()->artisan('app:prune-empty-organizations')->assertExitCode(0);

    expect(Organization::withTrashed()->whereKey($org->getKey())->exists())->toBeFalse();
});

it('keeps organizations that still have campaigns', function () {
    $org = Organization::factory()->withoutStripe()->create();
    Campaign::factory()->for($org)->create();
    trashOrganization($org);

    test()->artisan('app:prune-empty-organizations')->assertExitCode(0);

    expect(Organization::withTrashed()->whereKey($org->getKey())->exists())->toBeTrue();
});

it('keeps organizations that still have processing fees', function () {
    $org = Organization::factory()->withoutStripe()->create();
    ProcessingFee::factory()->for($org)->create();
    trashOrganization($org);

    test()->artisan('app:prune-empty-organizations')->assertExitCode(0);

    expect(Organization::withTrashed()->whereKey($org->getKey())->exists())->toBeTrue();
});

it('keeps organizations that were ever connected to stripe', function () {
    $org = trashOrganization(Organization::factory()->create());

    test()->artisan('app:prune-empty-organizations')->assertExitCode(0);

    expect(Organization::withTrashed()->whereKey($org->getKey())->exists())->toBeTrue();
});

it('keeps organizations trashed inside the retention window', function () {
    $org = trashOrganization(Organization::factory()->withoutStripe()->create(), daysAgo: 5);

    test()->artisan('app:prune-empty-organizations', ['--keep-days' => 30])->assertExitCode(0);

    expect(Organization::withTrashed()->whereKey($org->getKey())->exists())->toBeTrue();
});

it('never touches organizations that are still active', function () {
    $org = Organization::factory()->withoutStripe()->create();

    test()->artisan('app:prune-empty-organizations')->assertExitCode(0);

    expect(Organization::whereKey($org->getKey())->exists())->toBeTrue();
});

it('deletes nothing on a dry run', function () {
    $org = trashOrganization(Organization::factory()->withoutStripe()->create());

    test()->artisan('app:prune-empty-organizations', ['--dry-run' => true])->assertExitCode(0);

    expect(Organization::withTrashed()->whereKey($org->getKey())->exists())->toBeTrue();
});

it('schedules the prune so empty organizations are cleaned up automatically', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event): string => (string) $event->command);

    expect($commands->contains(fn (string $command): bool => str_contains($command, 'app:prune-empty-organizations')))->toBeTrue();
});
