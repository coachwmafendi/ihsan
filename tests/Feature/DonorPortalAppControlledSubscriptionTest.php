<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Queue;

function authenticateAsDonor(Organization $organization, Donor $donor): void
{
    test()->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $organization->getKey()]);
}

function createDonorPortalAppControlledSubscription(Organization $organization, Donor $donor, array $overrides = []): Subscription
{
    $campaign = Campaign::factory()->for($organization)->create();

    return Subscription::factory()->for($campaign)->for($donor)->create(array_merge([
        'stripe_subscription_id' => null,
        'stripe_price_id' => null,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'amount' => 30.00,
        'currency' => 'myr',
        'next_charge_at' => now()->addDay(),
    ], $overrides));
}

it('cancels an app-controlled recurring plan', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $subscription = createDonorPortalAppControlledSubscription($organization, $donor);

    authenticateAsDonor($organization, $donor);

    $this->post(route('donorportal.subscriptions.cancel', ['organization' => $organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $organization))
        ->assertSessionHas('success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->cancelled_at)->not->toBeNull()
        ->and($subscription->cancel_at_period_end)->toBeFalse()
        ->and($subscription->next_charge_at)->toBeNull();
});

it('pauses an app-controlled recurring plan', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $nextChargeAt = now()->addDay()->startOfSecond();
    $subscription = createDonorPortalAppControlledSubscription($organization, $donor, [
        'next_charge_at' => $nextChargeAt,
    ]);

    authenticateAsDonor($organization, $donor);

    $this->post(route('donorportal.subscriptions.pause', ['organization' => $organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $organization))
        ->assertSessionHas('success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Paused)
        ->and($subscription->paused_until)->not->toBeNull()
        ->and($subscription->next_charge_at)->not->toBeNull()
        ->and($subscription->paused_until->equalTo($subscription->next_charge_at))->toBeTrue()
        ->and($subscription->next_charge_at->isSameDay($nextChargeAt->addMonth()))->toBeTrue();
});

it('resumes an app-controlled recurring plan', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $subscription = createDonorPortalAppControlledSubscription($organization, $donor, [
        'status' => SubscriptionStatus::Paused,
        'paused_until' => now()->addWeek(),
        'next_charge_at' => now()->addWeek(),
    ]);

    authenticateAsDonor($organization, $donor);

    $this->post(route('donorportal.subscriptions.resume', ['organization' => $organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $organization))
        ->assertSessionHas('success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->paused_until)->toBeNull()
        ->and($subscription->next_charge_at)->not->toBeNull()
        ->and($subscription->next_charge_at->isFuture())->toBeTrue();
});

it('changes the amount of an app-controlled recurring plan', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $subscription = createDonorPortalAppControlledSubscription($organization, $donor, [
        'amount' => 30.00,
    ]);

    authenticateAsDonor($organization, $donor);

    $this->postJson(route('donorportal.subscriptions.change-amount', ['organization' => $organization, 'subscription' => $subscription]), [
        'new_amount' => 50.00,
    ])
        ->assertOk()
        ->assertJson(['success' => true, 'new_amount' => 50.00]);

    $subscription->refresh();

    expect($subscription->amount)->toEqual(50.00);
});

it('requires authentication to cancel an app-controlled recurring plan', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $subscription = createDonorPortalAppControlledSubscription($organization, $donor);

    $this->post(route('donorportal.subscriptions.cancel', ['organization' => $organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.login', $organization));
});

it('prevents one donor from cancelling another donors app-controlled plan', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();
    $subscription = createDonorPortalAppControlledSubscription($organization, $otherDonor);

    authenticateAsDonor($organization, $donor);

    $this->post(route('donorportal.subscriptions.cancel', ['organization' => $organization, 'subscription' => $subscription]))
        ->assertForbidden();
});
