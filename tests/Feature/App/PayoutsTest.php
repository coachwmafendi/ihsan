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
        ->assertSee('MYR 100.00')
        ->assertSeeHtml('100.00</td>')
        ->assertDontSeeHtml('200.00</td>');
});
