<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Livewire\App\Dashboard;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $this->donor = Donor::factory()->create();
});

function succeededDonationAt(string $when): Donation
{
    return Donation::factory()->for(test()->campaign)->for(test()->donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'created_at' => CarbonImmutable::parse($when),
    ]);
}

it('holds the custom dates until they are applied', function () {
    // Both fields were live, so the figures were recomputed on each half
    // entered date - including once against a range whose end was still the
    // previous one. Asserted on the template because Livewire's own set()
    // writes the property directly and so cannot tell the two bindings apart.
    $html = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'custom')
        ->html();

    expect($html)
        ->toContain('holdsUntilApplied: true')
        ->toContain('applyCustomRange')
        ->not->toContain('wire:model.live=\"customFrom\"')
        ->not->toContain('wire:model.live=\"customTo\"');
});

it('reads the held dates when apply is pressed', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-14 12:00:00', 'Asia/Kuala_Lumpur'));

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'custom')
        ->set('customFrom', '2026-01-01')
        ->set('customTo', '2026-01-31')
        ->call('applyCustomRange')
        ->assertHasNoErrors();

    [$from, $to] = $component->instance()->dateRangeLocal;

    expect($from->format('Y-m-d'))->toBe('2026-01-01');
    expect($to->format('Y-m-d'))->toBe('2026-01-31');
});

it('says so when the end date falls before the start', function () {
    // The range used to be swapped silently, so an organiser reading a report
    // for the wrong span had nothing on screen telling them it was not the one
    // they picked.
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'custom')
        ->set('customFrom', '2026-09-16')
        ->set('customTo', '2026-08-14')
        ->call('applyCustomRange')
        ->assertHasErrors(['customTo']);
});

it('offers the periods a report is actually written for', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('This month')
        ->assertSee('Last month')
        ->assertSee('Year to date')
        ->assertSee('All time');
});

it('stops the year to date at today rather than at new year', function () {
    // ReportingPeriod reads "this year" as the whole calendar year. The totals
    // are the same either way, but the trend would draw a bar for every month
    // that has not happened yet.
    $this->travelTo(CarbonImmutable::parse('2026-09-14 12:00:00', 'Asia/Kuala_Lumpur'));

    [$from, $to] = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'this_year')
        ->instance()
        ->dateRangeLocal;

    expect($from->format('Y-m-d'))->toBe('2026-01-01');
    expect($to->format('Y-m-d'))->toBe('2026-09-14');
});

it('runs all time from the first donation, not from an open end', function () {
    // An unbounded range reads as null, and the trend falls back to the last
    // seven days when it gets one - so the chart would have disagreed with the
    // totals above it.
    $this->travelTo(CarbonImmutable::parse('2026-09-14 12:00:00', 'Asia/Kuala_Lumpur'));

    succeededDonationAt('2026-03-02 08:00:00');

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'all_time');

    [$from, $to] = $component->instance()->dateRangeLocal;

    expect($from->format('Y-m-d'))->toBe('2026-03-02');
    expect($to->format('Y-m-d'))->toBe('2026-09-14');

    // The chart covers the same span it claims to.
    $frequency = $component->instance()->donationsByFrequency();

    expect($frequency['days'][0]['date_from_key'])->toBe('2026-03-02');
    expect(collect($frequency['days'])->sum('total'))->toBe(1);
});

it('falls back to today for an organisation with nothing to show yet', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-14 12:00:00', 'Asia/Kuala_Lumpur'));

    [$from, $to] = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'all_time')
        ->instance()
        ->dateRangeLocal;

    expect($from->format('Y-m-d'))->toBe('2026-09-14');
    expect($to->format('Y-m-d'))->toBe('2026-09-14');
});

it('hands the calendar the reporting timezone rather than the browser clock', function () {
    // The component used to ring "today" from new Date().toISOString(), which
    // is UTC - the wrong day for anyone in Kuala Lumpur reading before 8am.
    $this->travelTo(CarbonImmutable::parse('2026-09-14 02:00:00', 'UTC'));

    $html = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'custom')
        ->html();

    // 02:00 UTC is already the 14th in Kuala Lumpur.
    expect($html)->toContain("today: '2026-09-14'");
});

it('points the calendar at the properties the dashboard actually reads', function () {
    // The component takes its target property names as props. Two other call
    // sites passed them as wire:from, which Blade never maps to a prop, so the
    // calendar wrote to whatever the defaults were.
    $html = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('period', 'custom')
        ->html();

    expect($html)
        ->toContain("\$wire.set('customFrom'")
        ->toContain("\$wire.set('customTo'");
});
