<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Supporters\SupporterIndex;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
});

function supporterWith(array $emailState, Campaign $campaign): Donor
{
    $donor = Donor::factory()->create($emailState);

    Donation::factory()->for($campaign)->for($donor)->create(['status' => 'succeeded']);

    return $donor;
}

/**
 * The worst news wins: a donor who unsubscribed after a bounce still has an
 * address that does not work, and that is what an admin needs to know first.
 */
it('names the email delivery state, worst news first', function (array $state, string $expected) {
    expect(Donor::factory()->make($state)->emailDeliveryStatus()['label'])->toContain($expected);
})->with([
    'complaint outranks everything' => [[
        'email_complained_at' => now()->subDays(3),
        'email_bounced_at' => now()->subDays(5),
        'email_opt_out_at' => now()->subDays(6),
        'email_validated_at' => now()->subDays(9),
    ], 'Marked as spam'],
    'a bounce outranks an unsubscribe' => [[
        'email_complained_at' => null,
        'email_bounced_at' => now()->subDays(5),
        'email_opt_out_at' => now()->subDays(6),
        'email_validated_at' => now()->subDays(9),
    ], 'Bounced'],
    'an unsubscribe outranks a confirmation' => [[
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => now()->subDays(6),
        'email_validated_at' => now()->subDays(9),
    ], 'Unsubscribed'],
    'a confirmation when nothing went wrong' => [[
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => null,
        'email_validated_at' => now()->subDays(9),
    ], 'Email confirmed'],
    'nothing recorded at all' => [[
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => null,
        'email_validated_at' => null,
    ], 'Not confirmed yet'],
]);

it('dates the state it reports', function () {
    $donor = Donor::factory()->make([
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => null,
        'email_validated_at' => now()->setDate(2026, 9, 3),
    ]);

    expect($donor->emailDeliveryStatus()['label'])->toContain('3 Sep 2026');
});

/**
 * The tooltip used to repeat the address the cell already showed, which told
 * an admin nothing they could not read. Delivery state appears nowhere else in
 * this table.
 */
it('tells the supporters table something the row does not already show', function () {
    $donor = supporterWith(['email_bounced_at' => now()->setDate(2026, 9, 12), 'email_validated_at' => null], $this->campaign);

    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertSee('Bounced 12 Sep 2026')
        ->assertSee($donor->email);
});

it('opens the email tooltip downwards, clear of the column header', function () {
    supporterWith(['email_validated_at' => now()], $this->campaign);

    $html = Livewire::actingAs($this->user)->test(SupporterIndex::class)->html();

    expect($html)->toContain("position: 'bottom'");
});
