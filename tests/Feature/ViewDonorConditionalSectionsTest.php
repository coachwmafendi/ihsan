<?php

use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Filament\App\Resources\Donors\Pages\ViewDonor;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function renderSupporterViewWith(bool $hasDonation, bool $hasSubscription, DonationStatus $donationStatus = DonationStatus::Succeeded): Testable
{
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'name' => 'Nadia Supporter',
        'public_id' => 'DRGD3JY8',
    ]);

    if ($hasDonation) {
        Donation::factory()->for($campaign)->for($donor)->create([
            'invoice_number' => 'DONATION1',
            'status' => $donationStatus,
        ]);
    }

    if ($hasSubscription) {
        Subscription::factory()->for($campaign)->for($donor)->create([
            'amount' => 50.00,
        ]);
    }

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    return Livewire::test(ViewDonor::class, [
        'record' => $donor->public_id,
    ])->assertOk();
}

it('shows donations and recurring tables when the supporter has both', function () {
    renderSupporterViewWith(hasDonation: true, hasSubscription: true)
        ->assertSee('Information')
        ->assertSee('Nadia Supporter')
        ->assertSee('DRGD3JY8')
        ->assertDontSeeHtml('value="Nadia Supporter"')
        ->assertSeeHtml('supporter-view-shell')
        ->assertSeeHtml('supporter-view-nav')
        ->assertSeeHtml('id="receipts-section"')
        ->assertSeeHtml('id="donations-section"')
        ->assertSeeHtml('id="recurring-plans-section"')
        ->assertSee('DONATION1')
        ->assertSeeHtml("scrollTo('receipts-section')")
        ->assertSeeHtml("scrollTo('donations-section')")
        ->assertSeeHtml("scrollTo('recurring-plans-section')");
});

it('shows the receipts table for invoiced donations that are not downloadable yet', function () {
    renderSupporterViewWith(hasDonation: true, hasSubscription: false, donationStatus: DonationStatus::Failed)
        ->assertSeeHtml('id="receipts-section"')
        ->assertSee('DONATION1')
        ->assertSee('Unavailable')
        ->assertSeeHtml("scrollTo('receipts-section')");
});

it('hides the recurring table when the supporter only has donations', function () {
    renderSupporterViewWith(hasDonation: true, hasSubscription: false)
        ->assertSeeHtml('id="receipts-section"')
        ->assertSeeHtml('id="donations-section"')
        ->assertDontSeeHtml('id="recurring-plans-section"')
        ->assertSee('DONATION1')
        ->assertSeeHtml("scrollTo('receipts-section')")
        ->assertSeeHtml("scrollTo('donations-section')")
        ->assertDontSeeHtml("scrollTo('recurring-plans-section')");
});

it('hides the donations and receipts sections when the supporter only has recurring plans', function () {
    renderSupporterViewWith(hasDonation: false, hasSubscription: true)
        ->assertDontSeeHtml('id="donations-section"')
        ->assertDontSeeHtml('id="receipts-section"')
        ->assertSeeHtml('id="recurring-plans-section"')
        ->assertDontSeeHtml("scrollTo('donations-section')")
        ->assertDontSeeHtml("scrollTo('receipts-section')")
        ->assertSeeHtml("scrollTo('recurring-plans-section')");
});
