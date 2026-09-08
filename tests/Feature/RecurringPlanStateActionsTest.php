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

it('keeps the sticky sidebar within the screen', function () {
    // Stacked up, the panel ran past the bottom of the viewport and its last
    // items could not be scrolled to at all.
    $markup = file_get_contents(base_path('resources/views/livewire/app/subscriptions/show.blade.php'));

    expect($markup)
        ->toContain('lg:max-h-[calc(100vh-3rem)]')
        ->toContain('lg:overflow-y-auto');
});
