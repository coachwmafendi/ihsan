<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Mail\DonorDunningNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dunningPlan(array $attributes = []): Subscription
{
    $organization = Organization::factory()->create(['name' => 'Masjid Test']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Dana Test']);

    return Subscription::factory()->for($campaign)->for(Donor::factory())->create([
        'stripe_subscription_id' => null,
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::PastDue,
        'next_charge_at' => now()->addDays(7),
        ...$attributes,
    ]);
}

/**
 * The retry ladder is 1, 3, 7 then 7 days. By the third retry the next attempt
 * is a week away, and three more rounds follow it, so an email announcing a
 * final attempt tomorrow was wrong about both the day and the finality - and it
 * went to every donor who got that far.
 */
it('does not promise a final attempt tomorrow on the third retry', function () {
    $plan = dunningPlan();

    $mail = new DonorDunningNotification($plan, retryCount: 3);

    expect($mail->envelope()->subject)
        ->not->toContain('Tomorrow')
        ->not->toContain('Final Attempt');

    expect($mail->render())
        ->not->toContain('Tomorrow is the final attempt')
        ->not->toContain('Final Attempt Tomorrow');
});

it('names the day the next attempt actually falls on', function () {
    $plan = dunningPlan(['next_charge_at' => now()->addDays(7)]);

    expect((new DonorDunningNotification($plan, retryCount: 3))->render())
        ->toContain(myrTime($plan->next_charge_at, true, 'j M Y'));
});

it('leaves the earlier attempts reading as they did', function () {
    $plan = dunningPlan();

    expect((new DonorDunningNotification($plan, retryCount: 1))->envelope()->subject)
        ->toContain('Payment Failed');
});

/**
 * Stripe retries the plans it still bills. An app-controlled plan is retried by
 * this application, on a schedule of its own.
 */
it('does not credit Stripe with a retry this application performs', function () {
    $plan = dunningPlan();

    expect((new DonorDunningNotification($plan, retryCount: 1))->render())
        ->not->toContain('Stripe will retry');
});

it('says the same thing in Malay', function () {
    $plan = dunningPlan();
    $plan->donor->update(['locale' => 'ms']);
    $plan->refresh();

    $mail = new DonorDunningNotification($plan, retryCount: 3);

    expect($mail->envelope()->subject)->not->toContain('Esok');
    expect($mail->render())->not->toContain('Percubaan Akhir Esok');
});

/**
 * The final-attempt copy is not dead. Stripe-billed plans still use it from
 * ProcessStripeWebhook, where "final attempt" means Stripe's last retry and the
 * plan is still alive - there, "Last Chance to Update Payment" is exactly
 * right. It is only app-controlled plans that no longer send it, because by
 * then the plan has already ended and its own email says so.
 */
it('keeps the last-chance wording for a plan Stripe is still billing', function () {
    $plan = dunningPlan(['stripe_subscription_id' => 'sub_legacy_test']);

    $mail = new DonorDunningNotification($plan, retryCount: 4, isFinalAttempt: true);

    expect($mail->envelope()->subject)->toContain('Last Chance');
});
