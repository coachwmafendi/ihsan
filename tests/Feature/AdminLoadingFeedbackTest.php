<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdminToOrgAdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * The admin panel's slowest actions were its quietest.
 *
 * Filament's own button component reads its wire:click and swaps in a spinner
 * without being asked, so anything built with it was already covered. The
 * controls built by hand were not: a filter that runs the heaviest query on the
 * panel, a period switch that leaves the previous period's figures on screen
 * while it works, and a select that recounts everything below it.
 *
 * These assert the wiring rather than the pixels. A spinner that targets the
 * wrong thing shows on every request or on none, and both read as correct in a
 * screenshot taken at the wrong moment.
 */
beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
});

it('shows the transactions filter working while it runs', function () {
    // Filament's table indicator follows the table's own methods, and Apply
    // calls one of ours, so nothing on the page moved when it was pressed.
    $body = $this->actingAs($this->admin)->get('/admin/transactions')->assertOk()->getContent();

    expect($body)
        ->toContain('wire:target="applyFilters"')
        ->toContain('wire:target="clearAllFilters"');
});

it('dims the revenue figures while a new period is being fetched', function () {
    $body = $this->actingAs($this->admin)->get('/admin/revenue')->assertOk()->getContent();

    expect($body)
        ->toContain('wire:loading.class.delay.default="opacity-40"')
        ->toContain('wire:target="period"');
});

it('spins once for the revenue period switch, not once per button', function () {
    // Every button sets the same property, so a spinner inside each would start
    // six of them on one click.
    $body = $this->actingAs($this->admin)->get('/admin/revenue')->assertOk()->getContent();

    expect(substr_count($body, 'data-loading-spinner'))->toBe(1);
});

it('shows the fraud period select working while it recounts', function () {
    $body = $this->actingAs($this->admin)->get('/admin/fraud-prevention')->assertOk()->getContent();

    expect($body)
        ->toContain('wire:target="period"')
        ->toContain('data-loading-spinner');
});

it('spins only the row whose delete was pressed', function () {
    $org = Organization::factory()->create();

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => AdminToOrgAdminNotification::class,
        'notifiable_type' => User::class,
        'notifiable_id' => $this->admin->id,
        'data' => ['message' => 'Hello', 'type' => 'info', 'organization_id' => $org->id],
    ]);

    $body = $this->actingAs($this->admin)->get('/admin/send-notification-to-orgs')->assertOk()->getContent();

    // Without the argument, wire:target matches the method alone and every
    // row's button would react to any one of them.
    expect($body)->toContain("wire:target=\"deleteNotification('");
});

it('carries no dead loading attribute on the send button', function () {
    // wire:loading.label is not a Livewire modifier, and the element already
    // carries the wire:loading.attr Filament adds, so it was never honoured -
    // confirmed by clicking Send with it in place and watching nothing change.
    // Filament's own spinner is the one doing the work.
    $body = $this->actingAs($this->admin)->get('/admin/send-notification-to-orgs')->assertOk()->getContent();

    expect($body)->not->toContain('wire:loading.label');
});
