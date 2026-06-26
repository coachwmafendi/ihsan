<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->donor = Donor::factory()->create();

    $this->authenticateAsDonor = function (Donor $donor): void {
        test()->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $this->organization->getKey()]);
    };

    $this->createSubscription = function (Donor $donor, array $overrides = []): Subscription {
        $campaign = Campaign::factory()->for($this->organization)->create();

        return Subscription::factory()->for($campaign)->for($donor)->create(array_merge([
            'stripe_subscription_id' => null,
            'stripe_price_id' => null,
            'status' => SubscriptionStatus::Active,
            'interval' => SubscriptionInterval::Monthly,
            'amount' => 30.00,
            'currency' => 'myr',
            'next_charge_at' => now()->addDay(),
        ], $overrides));
    };
});

it('cancels an app-controlled recurring plan', function () {
    Queue::fake();

    $subscription = ($this->createSubscription)($this->donor);

    ($this->authenticateAsDonor)($this->donor);

    $this->post(route('donorportal.subscriptions.cancel', ['organization' => $this->organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $this->organization))
        ->assertSessionHas('success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->cancelled_at)->not->toBeNull()
        ->and($subscription->cancel_at_period_end)->toBeFalse()
        ->and($subscription->next_charge_at)->toBeNull();
});

it('pauses an app-controlled recurring plan', function () {
    Queue::fake();

    $nextChargeAt = now()->addDay()->startOfSecond();
    $subscription = ($this->createSubscription)($this->donor, [
        'next_charge_at' => $nextChargeAt,
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->post(route('donorportal.subscriptions.pause', ['organization' => $this->organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $this->organization))
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

    $subscription = ($this->createSubscription)($this->donor, [
        'status' => SubscriptionStatus::Paused,
        'paused_until' => now()->addWeek(),
        'next_charge_at' => now()->addWeek(),
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->post(route('donorportal.subscriptions.resume', ['organization' => $this->organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $this->organization))
        ->assertSessionHas('success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->paused_until)->toBeNull()
        ->and($subscription->next_charge_at)->not->toBeNull()
        ->and($subscription->next_charge_at->isFuture())->toBeTrue();
});

it('changes the amount of an app-controlled recurring plan', function () {
    Queue::fake();

    $subscription = ($this->createSubscription)($this->donor, [
        'amount' => 30.00,
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->postJson(route('donorportal.subscriptions.change-amount', ['organization' => $this->organization, 'subscription' => $subscription]), [
        'new_amount' => 50.00,
    ])
        ->assertOk()
        ->assertJson(['success' => true, 'new_amount' => 50.00]);

    $subscription->refresh();

    expect($subscription->amount)->toEqual(50.00);
});

it('requires authentication to cancel an app-controlled recurring plan', function () {
    $subscription = ($this->createSubscription)($this->donor);

    $this->post(route('donorportal.subscriptions.cancel', ['organization' => $this->organization, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.login', $this->organization));
});

it('prevents one donor from cancelling another donors app-controlled plan', function () {
    $otherDonor = Donor::factory()->create();
    $subscription = ($this->createSubscription)($otherDonor);

    ($this->authenticateAsDonor)($this->donor);

    $this->post(route('donorportal.subscriptions.cancel', ['organization' => $this->organization, 'subscription' => $subscription]))
        ->assertForbidden();
});
