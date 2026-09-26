<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Mail\FailedPaymentNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function pastDuePlan(array $attributes = []): Subscription
{
    $organization = Organization::factory()->create(['name' => 'Masjid Test']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Dana Test']);

    return Subscription::factory()->for($campaign)->for(Donor::factory())->create([
        'stripe_subscription_id' => null,
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::PastDue,
        'retry_count' => 1,
        'next_charge_at' => now()->addDays(3),
        ...$attributes,
    ]);
}

/**
 * App-controlled plans are retried by this application's own scheduler. Telling
 * the organization that Stripe would handle it named the wrong party and gave
 * them no date to expect anything on.
 */
it('names when the next attempt runs instead of crediting Stripe with it', function () {
    $plan = pastDuePlan();

    $html = (new FailedPaymentNotification($plan, 'Your card was declined.'))->render();

    expect($html)
        ->toContain(myrTime($plan->next_charge_at))
        ->not->toContain('Stripe will automatically retry');
});

it('leaves the retry to Stripe on a plan Stripe still bills', function () {
    $plan = pastDuePlan(['stripe_subscription_id' => 'sub_legacy_test']);

    $html = (new FailedPaymentNotification($plan, 'Your card was declined.'))->render();

    expect($html)->toContain('Stripe will retry this payment automatically');
});

/**
 * A card that blocks the purchase type declines the same way every time, so
 * "please monitor the situation" sent the organization off to wait for a
 * recovery that was never coming.
 */
it('says plainly when no further attempt can help', function () {
    $plan = pastDuePlan(['last_failure_code' => 'card_not_supported']);

    $html = (new FailedPaymentNotification($plan, 'Your card does not support this type of purchase.'))->render();

    expect($html)
        ->toContain('expected to fail in the same way')
        // The advice already written for this code, rather than a second
        // paraphrase of it.
        ->toContain('another card from the same donor often works');
});

it('carries the advice for a decline that may yet clear', function () {
    $plan = pastDuePlan(['last_failure_code' => 'insufficient_funds']);

    $html = (new FailedPaymentNotification($plan, 'Your card has insufficient funds.'))->render();

    expect($html)
        ->toContain('There was not enough in the account')
        ->not->toContain('expected to fail in the same way');
});

it('calls a plan that ran out of attempts failed, not past due', function () {
    $plan = pastDuePlan(['status' => SubscriptionStatus::Failed, 'next_charge_at' => null]);

    $html = (new FailedPaymentNotification($plan, 'Your card was declined.', isFinalAttempt: true))->render();

    expect($html)
        ->toContain('marked as failed')
        ->not->toContain('now past due');
});

it('still reads sensibly when the bank gave no code', function () {
    $plan = pastDuePlan(['last_failure_code' => null]);

    $html = (new FailedPaymentNotification($plan, 'Your card was declined.'))->render();

    expect($html)
        ->toContain('Your card was declined.')
        ->toContain(myrTime($plan->next_charge_at));
});
