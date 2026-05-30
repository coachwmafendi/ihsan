<?php

use App\Enums\UserRole;
use App\Filament\Pages\MonthlyInvoices;
use App\Models\MonthlyInvoice;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($this->admin);
});

it('can render the page', function () {
    $this->get('/admin/monthly-invoices')
        ->assertSuccessful()
        ->assertSee('ihsan-admin-page', false)
        ->assertSee('ihsan-admin-metric-card', false);
});

it('displays monthly invoices', function () {
    $invoice = MonthlyInvoice::factory()->create([
        'total_fees' => 150.00,
        'stripe_status' => 'open',
    ]);

    Livewire::test(MonthlyInvoices::class)
        ->assertSee($invoice->invoice_number)
        ->assertSee('MYR 150.00');
});

it('shows stats cards', function () {
    MonthlyInvoice::factory()->create(['total_fees' => 100, 'stripe_status' => 'open']);
    MonthlyInvoice::factory()->create(['total_fees' => 200, 'stripe_status' => 'paid']);

    Livewire::test(MonthlyInvoices::class)
        ->assertSet('totalOutstanding', '100.00')
        ->assertSet('totalCollected', '200.00');
});
