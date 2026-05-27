<?php

use App\Enums\UserRole;
use App\Filament\Pages\ProcessingFees;
use App\Models\Donation;
use App\Models\ProcessingFee;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($this->admin);
});

it('can render the page', function () {
    Livewire::test(ProcessingFees::class)
        ->assertSuccessful();
});

it('displays platform fees', function () {
    $fee = ProcessingFee::factory()->create([
        'fee_amount' => 25.00,
        'fee_percentage' => 2.5,
        'status' => 'pending',
    ]);

    Livewire::test(ProcessingFees::class)
        ->loadTable()
        ->assertSee('MYR 25.00')
        ->assertSee('Pending');
});

it('can filter by status', function () {
    ProcessingFee::factory()->create(['status' => 'pending', 'fee_amount' => 10]);
    ProcessingFee::factory()->create(['status' => 'paid', 'fee_amount' => 20]);

    Livewire::test(ProcessingFees::class)
        ->loadTable()
        ->assertCanSeeTableRecords(ProcessingFee::where('status', 'pending')->get())
        ->filterTable('status', 'pending')
        ->assertCountTableRecords(1);
});

it('can mark fee as paid', function () {
    $fee = ProcessingFee::factory()->create(['status' => 'pending']);

    Livewire::test(ProcessingFees::class)
        ->callTableAction('mark_paid', $fee);

    expect($fee->fresh()->status)->toBe('paid');
});

it('displays approximated MYR amount with tooltip for foreign currency donations', function () {
    $donation = Donation::factory()->create([
        'gross_amount' => 50.00,
        'currency' => 'usd',
        'base_currency' => 'myr',
        'base_amount' => 235.50,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => $donation->id,
        'fee_amount' => 5.88,
        'fee_percentage' => 2.5,
    ]);

    Livewire::test(ProcessingFees::class)
        ->loadTable()
        ->assertSee('≈ MYR 235.50')
        ->assertSee('USD 50.00'); // shown in tooltip
});

it('displays plain MYR amount for local currency donations', function () {
    $donation = Donation::factory()->create([
        'gross_amount' => 120.00,
        'currency' => 'myr',
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => $donation->id,
        'fee_amount' => 3.00,
        'fee_percentage' => 2.5,
    ]);

    Livewire::test(ProcessingFees::class)
        ->loadTable()
        ->assertSee('MYR 120.00')
        ->assertDontSee('≈');
});
