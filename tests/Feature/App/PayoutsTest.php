<?php

declare(strict_types=1);

use App\Livewire\App\Payouts as PayoutsPage;
use App\Models\Organization;
use App\Models\Payout;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->for($this->organization)->create();
});

it('redirects guests to login', function () {
    $this->get('https://app.example.test/payouts')
        ->assertRedirect(route('login'));
});

it('renders for authenticated ngo admin', function () {
    $this->actingAs($this->user)
        ->get('https://app.example.test/payouts')
        ->assertOk()
        ->assertSee('Payouts')
        ->assertSee('Paid This Month');
});

it('shows payouts for the organization', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now(),
        'bank_name' => 'Maybank',
        'bank_account_last4' => '1234',
    ]);

    $this->actingAs($this->user)
        ->get('https://app.example.test/payouts')
        ->assertOk()
        ->assertSee('MYR 100.00')
        ->assertSee('Maybank')
        ->assertSee('****1234');
});

it('does not show payouts from other organizations', function () {
    $otherOrganization = Organization::factory()->stripeConnected()->create();

    Payout::factory()->for($otherOrganization)->create([
        'amount' => 99900,
        'status' => 'paid',
        'arrival_date' => now(),
    ]);

    $this->actingAs($this->user)
        ->get('https://app.example.test/payouts')
        ->assertOk()
        ->assertDontSee('999.00');
});

it('filters payouts by status', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now(),
    ]);

    Payout::factory()->for($this->organization)->create([
        'amount' => 20000,
        'status' => 'pending',
        'arrival_date' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('statusFilter', 'paid')
        ->call('applyStatusFilter')
        ->assertSee('MYR 100.00')
        ->assertSeeHtml('100.00</td>')
        ->assertDontSeeHtml('200.00</td>');
});

it('filters payouts by date in the last N days', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(2),
    ]);

    Payout::factory()->for($this->organization)->create([
        'amount' => 20000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(30),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('dateValue', 7)
        ->set('dateUnit', 'days')
        ->call('applyDateFilter')
        ->assertSeeHtml('100.00</td>')
        ->assertDontSeeHtml('200.00</td>');
});

it('filters payouts on or after a date', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(5),
    ]);

    Payout::factory()->for($this->organization)->create([
        'amount' => 20000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(15),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('dateOperator', 'on_or_after')
        ->set('dateSingle', now()->subDays(7)->format('Y-m-d'))
        ->call('applyDateFilter')
        ->assertSeeHtml('100.00</td>')
        ->assertDontSeeHtml('200.00</td>');
});

it('filters payouts before or on a date', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(15),
    ]);

    Payout::factory()->for($this->organization)->create([
        'amount' => 20000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(5),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('dateOperator', 'before_or_on')
        ->set('dateSingle', now()->subDays(7)->format('Y-m-d'))
        ->call('applyDateFilter')
        ->assertSeeHtml('100.00</td>')
        ->assertDontSeeHtml('200.00</td>');
});

it('filters payouts between two dates', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(5),
    ]);

    Payout::factory()->for($this->organization)->create([
        'amount' => 20000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(20),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('dateOperator', 'between')
        ->set('pendingDateFrom', now()->subDays(10)->format('Y-m-d'))
        ->set('pendingDateTo', now()->subDays(2)->format('Y-m-d'))
        ->call('applyDateFilter')
        ->assertSeeHtml('100.00</td>')
        ->assertDontSeeHtml('200.00</td>');
});

it('filters payouts equal to a specific date', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(3),
    ]);

    Payout::factory()->for($this->organization)->create([
        'amount' => 20000,
        'status' => 'paid',
        'arrival_date' => now()->subDays(5),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('dateOperator', 'equal')
        ->set('dateSingle', now()->subDays(3)->format('Y-m-d'))
        ->call('applyDateFilter')
        ->assertSeeHtml('100.00</td>')
        ->assertDontSeeHtml('200.00</td>');
});

it('filters payouts by amount greater than a value', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now(),
    ]);

    Payout::factory()->for($this->organization)->create([
        'amount' => 99900,
        'status' => 'paid',
        'arrival_date' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('amountOperator', 'greater_than')
        ->set('amountValue', 500.00)
        ->call('applyAmountFilter')
        ->assertSeeHtml('999.00</td>')
        ->assertDontSeeHtml('100.00</td>');
});

it('clears all filters', function () {
    Payout::factory()->for($this->organization)->create([
        'amount' => 10000,
        'status' => 'paid',
        'arrival_date' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->set('statusFilter', 'pending')
        ->call('applyStatusFilter')
        ->call('clearFilters')
        ->assertSeeHtml('100.00</td>');
});

it('renders its filters with the shared chip component', function () {
    // The page used to hand-roll rounded-full teal pills, which looked nothing
    // like the chips on every other list page.
    $html = Livewire::actingAs($this->user)
        ->test(PayoutsPage::class)
        ->html();

    expect(substr_count($html, 'rounded-md border px-2.5 py-1.5 text-xs'))->toBeGreaterThanOrEqual(3)
        ->and($html)->not->toContain('rounded-full border px-3 py-1.5 text-sm font-medium')
        ->and($html)->toContain('Reset filters')
        // Native selects draw their arrow against the border whatever the
        // padding says, so the panels draw their own with room to breathe.
        ->and(substr_count($html, 'appearance-none'))->toBe(4)
        ->and(substr_count($html, 'right-3 my-auto size-4'))->toBe(4);
});
