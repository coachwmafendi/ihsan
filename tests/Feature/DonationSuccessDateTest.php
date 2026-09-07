<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Livewire\App\Donations\DonationShow;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Only the CHIP path ever wrote down when a donation went through, so the page
 * read the moment off updated_at instead - which a later edit or a refund
 * quietly overwrote, and which vanished entirely once the status changed.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
});

function showDonation(Donation $donation): Testable
{
    return Livewire::actingAs(test()->user)->test(DonationShow::class, ['donation' => $donation]);
}

it('reports when the payment went through, not when the row last changed', function () {
    $donation = Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Succeeded,
        'finalized_at' => now()->subDays(3),
    ]);

    // An edit today must not make the donation look like it succeeded today.
    $donation->touch();

    expect(showDonation($donation->fresh())->instance()->successDate())
        ->toBe(myrTime(now()->subDays(3)));
});

it('still reports the success date after a refund', function () {
    // The page shows the original transaction beside it, so saying nothing here
    // read as though the donation had never succeeded at all.
    $donation = Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Refunded,
        'finalized_at' => now()->subDay(),
        'refunded_at' => now(),
    ]);

    expect(showDonation($donation)->instance()->successDate())->not->toBeNull();
});

it('says nothing for a donation that never went through', function () {
    $donation = Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Failed,
        'finalized_at' => null,
    ]);

    expect(showDonation($donation)->instance()->successDate())->toBeNull();
});

it('falls back for donations taken before the moment was recorded', function () {
    // Every Stripe donation before this change has no finalized_at, and the
    // page still has to show them something.
    $donation = Donation::factory()->for($this->campaign)->create([
        'status' => DonationStatus::Succeeded,
        'finalized_at' => null,
    ]);

    expect(showDonation($donation)->instance()->successDate())->not->toBeNull();
});
