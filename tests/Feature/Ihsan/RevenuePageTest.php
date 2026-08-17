<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Filament\Pages\Revenue;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\User;
use Livewire\Livewire;

it('defaults to this month period to match per organization report downloads', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $component = Livewire::actingAs($user)->test(Revenue::class);

    expect($component->get('period'))->toBe('this_month');
});

it('shows revenue metrics to super admins', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'processing_fee' => 3.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => $donation->id,
        'organization_id' => $org->id,
        'fee_amount' => 3.00,
        'status' => 'paid',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)
        ->get('/admin/revenue')
        ->assertSuccessful()
        ->assertSee('ihsan-admin-toolbar', false)
        ->assertSee('ihsan-admin-segmented', false)
        ->assertSee('3.00');
});

it('denies revenue page to ngo admins', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);

    $this->actingAs($user)
        ->get('/admin/revenue')
        ->assertForbidden();
});

it('displays average donation size', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
    ]);
    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 200.00,
        'base_amount' => 200.00,
        'status' => DonationStatus::Succeeded,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($user)
        ->test(Revenue::class)
        ->assertSee('MYR 150.00');
});

it('displays fee breakdown by status', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    $paid = Donation::factory()->for($campaign)->for($donor)->create(['status' => DonationStatus::Succeeded]);
    $pending = Donation::factory()->for($campaign)->for($donor)->create(['status' => DonationStatus::Succeeded]);
    $invoiced = Donation::factory()->for($campaign)->for($donor)->create(['status' => DonationStatus::Succeeded]);
    $failed = Donation::factory()->for($campaign)->for($donor)->create(['status' => DonationStatus::Succeeded]);

    ProcessingFee::factory()->create(['donation_id' => $paid->id, 'organization_id' => $org->id, 'fee_amount' => 10.00, 'status' => 'paid']);
    ProcessingFee::factory()->create(['donation_id' => $pending->id, 'organization_id' => $org->id, 'fee_amount' => 5.00, 'status' => 'pending']);
    ProcessingFee::factory()->create(['donation_id' => $invoiced->id, 'organization_id' => $org->id, 'fee_amount' => 3.00, 'status' => 'invoiced']);
    ProcessingFee::factory()->create(['donation_id' => $failed->id, 'organization_id' => $org->id, 'fee_amount' => 2.00, 'status' => 'failed']);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($user)
        ->test(Revenue::class)
        ->assertSee('MYR 10.00')
        ->assertSee('MYR 5.00')
        ->assertSee('MYR 3.00')
        ->assertSee('MYR 2.00');
});

it('filters revenue by period', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
        'created_at' => today(),
    ]);
    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 250.00,
        'base_amount' => 250.00,
        'status' => DonationStatus::Succeeded,
        'created_at' => today()->subYear(),
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($user)
        ->test(Revenue::class)
        ->set('period', 'today')
        ->assertSee('MYR 100.00')
        ->assertDontSee('MYR 250.00')
        ->set('period', 'all_time')
        ->assertSee('MYR 350.00');
});

it('shows avg donation and effective rate per organization', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 200.00,
        'base_amount' => 200.00,
        'status' => DonationStatus::Succeeded,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => Donation::first()->id,
        'organization_id' => $org->id,
        'fee_amount' => 5.00,
        'status' => 'paid',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($user)
        ->test(Revenue::class)
        ->assertSee('MYR 200.00')
        ->assertSee('2.50%'); // 5 / 200 = 2.5%
});

it('shows a total summary row across all organizations', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->count(2)->for($campaign)->for($donor)->sequence(
        ['gross_amount' => 100.00, 'base_amount' => 100.00],
        ['gross_amount' => 200.00, 'base_amount' => 200.00]
    )->create(['status' => DonationStatus::Succeeded]);

    $donations = Donation::all();

    ProcessingFee::factory()->create([
        'donation_id' => $donations->first()->id,
        'organization_id' => $org->id,
        'fee_amount' => 3.00,
        'status' => 'paid',
    ]);
    ProcessingFee::factory()->create([
        'donation_id' => $donations->last()->id,
        'organization_id' => $org->id,
        'fee_amount' => 6.00,
        'status' => 'paid',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($user)
        ->test(Revenue::class)
        ->assertSee('Total')
        ->assertSee('2') // total donations count
        ->assertSee('MYR 300.00') // total volume
        ->assertSee('MYR 150.00') // average donation
        ->assertSee('MYR 9.00') // total processing fees
        ->assertSee('3.00%'); // effective rate = 9 / 300
});

it('matches per organization csv download for the same period', function () {
    $org = Organization::factory()->create(['status' => 'active', 'name' => 'Matching Org']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 300.00,
        'base_amount' => 300.00,
        'stripe_fee' => 4.50,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    ProcessingFee::factory()->create([
        'donation_id' => Donation::first()->id,
        'organization_id' => $org->id,
        'fee_amount' => 7.50,
        'status' => 'paid',
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $period = 'this_month';

    Livewire::actingAs($user)
        ->test(Revenue::class)
        ->set('period', $period)
        ->assertSee('MYR 300.00')
        ->assertSee('MYR 7.50')
        ->assertSee('Matching Org');

    $response = $this->actingAs($user)
        ->get(route('filament.admin.pages.revenue.report', [
            'organizationPublicId' => $org->public_id,
            'format' => 'csv',
            'period' => $period,
        ]));

    $response->assertOk();

    $content = $response->streamedContent();
    expect($content)
        ->toContain('Matching Org')
        ->toContain('300.00')
        ->toContain('4.50')
        ->toContain('7.50');
});

it('updates per organization download links when the period filter changes', function () {
    $org = Organization::factory()->create(['status' => 'active', 'name' => 'Link Test Org']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $component = Livewire::actingAs($user)
        ->test(Revenue::class)
        ->set('period', 'this_year');

    $html = $component->html();
    $expectedUrl = route('filament.admin.pages.revenue.report', [
        'organizationPublicId' => $org->public_id,
        'format' => 'pdf',
        'period' => 'this_year',
    ]);

    expect($html)->toContain($expectedUrl);
});

it('shows all-organizations export links that reflect the selected period', function () {
    $org = Organization::factory()->create(['status' => 'active']);
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
    ]);

    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $component = Livewire::actingAs($user)
        ->test(Revenue::class)
        ->set('period', 'last_month');

    $html = $component->html();
    $csvUrl = route('filament.admin.pages.revenue.report.all', [
        'format' => 'csv',
        'period' => 'last_month',
    ]);
    $pdfUrl = route('filament.admin.pages.revenue.report.all', [
        'format' => 'pdf',
        'period' => 'last_month',
    ]);

    expect($html)
        ->toContain($csvUrl)
        ->toContain($pdfUrl)
        ->toContain('Download CSV report for all organizations')
        ->toContain('Download PDF report for all organizations');
});
