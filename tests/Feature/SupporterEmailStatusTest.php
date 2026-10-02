<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Supporters\SupporterIndex;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    ], 'Marked as spam on'],
    'a bounce outranks an unsubscribe' => [[
        'email_complained_at' => null,
        'email_bounced_at' => now()->subDays(5),
        'email_opt_out_at' => now()->subDays(6),
        'email_validated_at' => now()->subDays(9),
    ], 'Bounced on'],
    'an unsubscribe outranks a confirmation' => [[
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => now()->subDays(6),
        'email_validated_at' => now()->subDays(9),
    ], 'Unsubscribed on'],
    'a confirmation when nothing went wrong' => [[
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => null,
        'email_validated_at' => now()->subDays(9),
    ], 'Delivered on'],
    'nothing recorded at all' => [[
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => null,
        'email_validated_at' => null,
    ], 'No email sent yet'],
]);

it('dates the state it reports', function () {
    $donor = Donor::factory()->make([
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => null,
        'email_validated_at' => CarbonImmutable::parse('2026-09-03 12:00', 'Asia/Kuala_Lumpur'),
    ]);

    expect($donor->emailDeliveryStatus()['label'])->toContain('3 Sep 2026');
});

/**
 * The tooltip used to repeat the address the cell already showed, which told
 * an admin nothing they could not read. Delivery state appears nowhere else in
 * this table.
 */
it('tells the supporters table something the row does not already show', function () {
    $donor = supporterWith(['email_bounced_at' => CarbonImmutable::parse('2026-09-12 12:00', 'Asia/Kuala_Lumpur'), 'email_validated_at' => null], $this->campaign);

    Livewire::actingAs($this->user)
        ->test(SupporterIndex::class)
        ->assertSee('Bounced on 12 Sep 2026')
        ->assertSee($donor->email);
});

it('opens the email tooltip downwards, clear of the column header', function () {
    supporterWith(['email_validated_at' => now()], $this->campaign);

    $html = Livewire::actingAs($this->user)->test(SupporterIndex::class)->html();

    expect($html)->toContain("position: 'bottom'");
});

/**
 * email_validated_at is stamped when Amazon SES reports a delivery or an open
 * (EmailWebhookService), not by any action the donor took - this application
 * has no confirmation link. Wording that implies otherwise is a lie the tooltip
 * would tell on every row.
 */
it('does not claim the donor confirmed anything', function () {
    $donor = Donor::factory()->make([
        'email_complained_at' => null,
        'email_bounced_at' => null,
        'email_opt_out_at' => null,
        'email_validated_at' => now(),
    ]);

    expect($donor->emailDeliveryStatus()['label'])
        ->toContain('Delivered')
        ->not->toContain('confirm');
});

/**
 * Two rows out of 216 on production actually need attention. Marking the other
 * 214 as healthy would bury the two that matter, so the list stays quiet until
 * something is wrong.
 */
it('marks only the supporters whose email is not getting through', function (array $state, ?string $expected) {
    supporterWith($state, $this->campaign);

    $html = Livewire::actingAs($this->user)->test(SupporterIndex::class)->html();

    if ($expected === null) {
        expect($html)->not->toContain('data-email-flag');
    } else {
        expect($html)->toContain('data-email-flag="'.$expected.'"');
    }
})->with([
    'a spam complaint is flagged' => [['email_complained_at' => now()], 'red'],
    'a bounce is flagged' => [['email_bounced_at' => now()], 'red'],
    'an unsubscribe is flagged' => [['email_opt_out_at' => now()], 'amber'],
    'a delivered address is left alone' => [['email_validated_at' => now()], null],
    'an address with no record yet is left alone' => [[], null],
]);

/**
 * "No delivery recorded yet" covered two different situations: nobody has ever
 * written to this address, and mail went out and nothing came back. On
 * production that was 14 of one and 10 of the other, nine of them more than a
 * fortnight old, all reading identically.
 */
it('separates an address nobody wrote to from one that went silent', function () {
    $neverWritten = supporterWith([], $this->campaign);

    $wroteButSilent = supporterWith([], $this->campaign);
    DonorEmailLog::factory()->create([
        'donor_id' => $wroteButSilent->id,
        'created_at' => CarbonImmutable::parse('2026-09-18 12:00', 'Asia/Kuala_Lumpur'),
        'delivered_at' => null,
        'opened_at' => null,
    ]);

    expect($neverWritten->fresh()->emailDeliveryStatus()['label'])->toBe('No email sent yet')
        ->and($wroteButSilent->fresh()->emailDeliveryStatus()['label'])->toContain('18 Sep 2026')
        ->and($wroteButSilent->fresh()->emailDeliveryStatus()['label'])->toContain('no delivery confirmed');
});

it('leaves both of those unmarked in the list', function () {
    supporterWith([], $this->campaign);

    $html = Livewire::actingAs($this->user)->test(SupporterIndex::class)->html();

    expect($html)->not->toContain('data-email-flag');
});

it('reads the supporters list without a query per row', function () {
    for ($i = 0; $i < 12; $i++) {
        supporterWith(['email_validated_at' => now()], $this->campaign);
    }

    DB::enableQueryLog();
    Livewire::actingAs($this->user)->test(SupporterIndex::class)->html();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThan(30);
});

/**
 * Mail went out and nothing came back. That is not a failure - it may well have
 * arrived and SES never said so - but nine of the ten on production had been
 * silent for a fortnight or more, and none of it was visible without hovering.
 */
it('marks an address that went silent, without calling it broken', function () {
    $silent = supporterWith([], $this->campaign);
    DonorEmailLog::factory()->create([
        'donor_id' => $silent->id,
        'created_at' => now()->subDays(20),
        'delivered_at' => null,
        'opened_at' => null,
    ]);

    $html = Livewire::actingAs($this->user)->test(SupporterIndex::class)->html();

    expect($html)->toContain('data-email-flag="slate"')
        ->not->toContain('data-email-flag="red"')
        ->not->toContain('data-email-flag="amber"');
});

it('leaves an address nobody has written to unmarked', function () {
    supporterWith([], $this->campaign);

    expect(Livewire::actingAs($this->user)->test(SupporterIndex::class)->html())
        ->not->toContain('data-email-flag');
});
