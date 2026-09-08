<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Livewire\App\Subscriptions\SubscriptionIndex;
use App\Livewire\App\Subscriptions\SubscriptionShow;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

/**
 * The panel only ever offered actions on an active plan. Everything else fell
 * through to "This recurring plan has ended", which was false for a paused plan
 * and left no way back from it - the only route out was writing SQL by hand.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
});

function planWith(SubscriptionStatus $status, array $attributes = []): Subscription
{
    return Subscription::factory()->for(test()->campaign)->create(array_merge([
        'status' => $status,
        // App-controlled: these are the plans this panel schedules itself.
        'stripe_subscription_id' => null,
        'created_at' => now()->subMonths(2)->setDay(8),
    ], $attributes));
}

function plan(Subscription $subscription): Testable
{
    return Livewire::actingAs(test()->user)->test(SubscriptionShow::class, ['subscription' => $subscription]);
}

it('offers a paused plan a way back', function () {
    $subscription = planWith(SubscriptionStatus::Paused, [
        'paused_until' => now()->addMonths(3),
        'next_charge_at' => now()->addMonths(3),
    ]);

    plan($subscription)
        ->assertSee('Resume plan')
        ->assertDontSee('This recurring plan has ended');
});

it('brings a resumed plan back to its own billing day, not further away', function () {
    // Resuming used to add an interval to a date already months out, so every
    // resume pushed the donor's next charge another month into the future.
    $subscription = planWith(SubscriptionStatus::Paused, [
        'paused_until' => now()->addMonths(3),
        'next_charge_at' => now()->addMonths(3),
    ]);

    plan($subscription)->call('resumeSubscription');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->paused_until)->toBeNull()
        ->and($subscription->next_charge_at->isAfter(now()))->toBeTrue()
        ->and($subscription->next_charge_at->isBefore(now()->addMonths(2)))->toBeTrue();
});

it('lets a cancelled plan be started again, once someone confirms it', function () {
    // The supporter was told it had stopped, so this cannot be a single click.
    $subscription = planWith(SubscriptionStatus::Cancelled, [
        'cancelled_at' => now()->subDay(),
        'cancellation_reason' => 'Cancelled by mistake',
        'next_charge_at' => null,
    ]);

    $component = plan($subscription)->assertSee('Reactivate plan');

    $component->call('openReactivateModal')->assertSet('showReactivateModal', true);

    $component->call('reactivateSubscription');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->cancelled_at)->toBeNull()
        ->and($subscription->cancellation_reason)->toBeNull()
        ->and($subscription->next_charge_at)->not->toBeNull();
});

it('refuses to resume a plan that was never paused', function () {
    $subscription = planWith(SubscriptionStatus::Cancelled, ['cancelled_at' => now()]);

    plan($subscription)->call('resumeSubscription');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
});

it('still calls a completed plan ended', function () {
    $subscription = planWith(SubscriptionStatus::Completed);

    plan($subscription)
        ->assertSee('This recurring plan has ended')
        ->assertDontSee('Resume plan');
});

it('does not promise an installment a stopped plan will never take', function (SubscriptionStatus $status, string $expected) {
    // The tooltip fell back to the old period end when there was no next charge,
    // so a cancelled plan still advertised a date months away.
    $subscription = planWith($status, [
        'next_charge_at' => null,
        'current_period_end' => now()->addMonth(),
        'cancelled_at' => $status === SubscriptionStatus::Cancelled ? now() : null,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertSee($expected);
})->with([
    'cancelled' => [SubscriptionStatus::Cancelled, 'no further installments'],
    'completed' => [SubscriptionStatus::Completed, 'Completed — no further installments'],
]);

it('says when a paused plan comes back rather than naming an installment', function () {
    planWith(SubscriptionStatus::Paused, [
        'paused_until' => now()->addMonths(3),
        'next_charge_at' => now()->addMonths(3),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertSee('Paused — resumes on');
});

it('lets a past-due plan be retried without touching the database by hand', function () {
    // The charge action only takes active plans, so a past-due one sat stranded
    // until its own retry came round - recovering ours meant writing SQL.
    $subscription = planWith(SubscriptionStatus::PastDue, [
        'retry_count' => 1,
        'next_charge_at' => now()->addDay(),
    ]);

    plan($subscription)
        ->assertSee('Retry payment now')
        ->assertSee('Update payment details')
        ->assertDontSee('This recurring plan has ended');
});

it('offers a failed plan the same way back as a cancelled one', function () {
    $subscription = planWith(SubscriptionStatus::Failed, ['next_charge_at' => null]);

    plan($subscription)
        ->assertSee('Reactivate plan')
        ->assertSee('no retries remain');

    plan($subscription)->call('reactivateSubscription');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

it('says a plan that never started never started', function (SubscriptionStatus $status) {
    // Calling these "ended" suggested something had run and stopped.
    plan(planWith($status))
        ->assertSee('This plan never started')
        ->assertDontSee('This recurring plan has ended');
})->with([
    'incomplete' => [SubscriptionStatus::Incomplete],
    'expired' => [SubscriptionStatus::IncompleteExpired],
]);

it('refuses to retry a plan that is not past due', function () {
    $subscription = planWith(SubscriptionStatus::Active, ['next_charge_at' => now()->addMonth()]);

    plan($subscription)->call('retryInstallmentNow');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->fresh()->next_charge_at->isFuture())->toBeTrue();
});

it('does not give reactivate the same icon as the plans nav item', function () {
    // Side by side they were indistinguishable, and one navigates while the
    // other starts charging a supporter again.
    $markup = file_get_contents(base_path('resources/views/livewire/app/subscriptions/show.blade.php'));

    expect($markup)->toContain('<x-heroicon-o-arrow-uturn-left class="size-5 text-slate-400" />
                            Reactivate plan');
});

it('keeps the side panel in view without hiding its own contents', function () {
    // Pinned at a fixed offset, a panel taller than the window kept its last
    // items below the fold for as long as the page was scrolled. Capping its
    // height instead put those items behind a scrollbar of its own.
    $markup = file_get_contents(base_path('resources/views/livewire/app/subscriptions/show.blade.php'));

    expect($markup)
        ->toContain('<x-ui.sticky-panel')
        ->not->toContain('lg:overflow-y-auto');

    $panel = file_get_contents(base_path('resources/views/components/ui/sticky-panel.blade.php'));

    // The offset follows the panel's height, so a tall one is pinned by its
    // bottom edge and the page carries it up until everything has been seen.
    expect($panel)
        ->toContain('lg:sticky')
        ->toContain('window.innerHeight')
        ->toContain('ResizeObserver');

    // And it clears the app header, which is sticky and would otherwise cover
    // the panel's first items - measured rather than written down twice.
    expect($panel)->toContain("document.querySelector('header.sticky')");

    expect(file_get_contents(base_path('resources/views/livewire/app/topbar.blade.php')))
        ->toContain('<header class="sticky top-0');
});

it('uses the same panel on the donation page', function () {
    expect(file_get_contents(base_path('resources/views/livewire/app/donations/show.blade.php')))
        ->toContain('<x-ui.sticky-panel');
});

/**
 * Cancelling wrote a full audit entry; pausing, skipping, resuming and
 * reactivating wrote nothing at all. Reactivating is the one that starts
 * taking money from someone who was told their plan had stopped, and it was
 * the quietest of the lot.
 */
it('records who paused a plan and for how long', function () {
    $subscription = planWith(SubscriptionStatus::Active, ['next_charge_at' => now()->addDays(5)]);

    plan($subscription)->call('pauseSubscription');

    $activity = Activity::query()->where('event', 'subscription.paused')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($this->user->getKey())
        ->and($activity->properties->get('initiator'))->toBe('admin')
        ->and($activity->properties->get('months'))->toBe(1)
        ->and($activity->description)->toContain($subscription->public_id);
});

it('records a skip as the pause it is, with the months asked for', function () {
    $subscription = planWith(SubscriptionStatus::Active, ['next_charge_at' => now()->addDays(5)]);

    plan($subscription)
        ->set('skipDuration', '3')
        ->call('confirmSkip');

    $activity = Activity::query()->where('event', 'subscription.paused')->latest('id')->first();

    expect($activity?->properties->get('months'))->toBe(3)
        ->and($activity?->properties->get('skipped'))->toBeTrue();
});

it('records who resumed a paused plan', function () {
    $subscription = planWith(SubscriptionStatus::Paused, [
        'paused_until' => now()->addMonths(3),
        'next_charge_at' => now()->addMonths(3),
    ]);

    plan($subscription)->call('resumeSubscription');

    $activity = Activity::query()->where('event', 'subscription.resumed')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($this->user->getKey())
        ->and($activity->properties->get('initiator'))->toBe('admin');
});

it('records a reactivation with the status it came from and when charging resumes', function () {
    $subscription = planWith(SubscriptionStatus::Cancelled, [
        'cancelled_at' => now()->subDay(),
        'next_charge_at' => null,
    ]);

    plan($subscription)->call('reactivateSubscription');

    $activity = Activity::query()->where('event', 'subscription.reactivated')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($this->user->getKey())
        ->and($activity->properties->get('initiator'))->toBe('admin')
        ->and($activity->properties->get('from_status'))->toBe('cancelled')
        ->and($activity->properties->get('next_charge_at'))->not->toBeNull();
});

it('records an admin cancellation once, with the reason given', function () {
    // The cancel action logged as well as the caller, so every cancellation
    // from the donor portal was written down twice.
    $subscription = planWith(SubscriptionStatus::Active, ['next_charge_at' => now()->addDays(5)]);

    plan($subscription)
        ->set('cancelReason', 'Duplicate plan')
        ->call('cancelSubscription');

    $entries = Activity::query()->where('event', 'subscription.cancelled')->get();

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->properties->get('reason'))->toBe('Duplicate plan')
        ->and($entries->first()->properties->get('initiator'))->toBe('admin');
});
