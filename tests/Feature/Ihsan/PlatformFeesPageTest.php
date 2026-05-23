<?php

use App\Enums\UserRole;
use App\Filament\Pages\PlatformFees;
use App\Models\PlatformFee;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($this->admin);
});

it('can render the page', function () {
    Livewire::test(PlatformFees::class)
        ->assertSuccessful();
});

it('displays platform fees', function () {
    $fee = PlatformFee::factory()->create([
        'fee_amount' => 25.00,
        'fee_percentage' => 2.5,
        'status' => 'pending',
    ]);

    Livewire::test(PlatformFees::class)
        ->loadTable()
        ->assertSee('MYR 25.00')
        ->assertSee('Pending');
});

it('can filter by status', function () {
    PlatformFee::factory()->create(['status' => 'pending', 'fee_amount' => 10]);
    PlatformFee::factory()->create(['status' => 'paid', 'fee_amount' => 20]);

    Livewire::test(PlatformFees::class)
        ->loadTable()
        ->assertCanSeeTableRecords(PlatformFee::where('status', 'pending')->get())
        ->filterTable('status', 'pending')
        ->assertCountTableRecords(1);
});

it('can mark fee as paid', function () {
    $fee = PlatformFee::factory()->create(['status' => 'pending']);

    Livewire::test(PlatformFees::class)
        ->callTableAction('mark_paid', $fee);

    expect($fee->fresh()->status)->toBe('paid');
});
