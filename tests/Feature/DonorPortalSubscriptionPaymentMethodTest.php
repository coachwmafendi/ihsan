<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;

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
            'chip_recurring_token' => null,
            'status' => SubscriptionStatus::Active,
            'interval' => SubscriptionInterval::Monthly,
            'amount' => 30.00,
            'currency' => 'myr',
            'next_charge_at' => now()->addDay(),
        ], $overrides));
    };
});

it('rejects client secret request for chip recurring subscription', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'chip_recurring_token' => 'chip-token-123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->getJson(route('donorportal.subscriptions.payment-method.client-secret', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]))
        ->assertUnprocessable()
        ->assertJson([
            'error' => 'Updating payment method is not supported for CHIP recurring subscriptions.',
        ]);
});

it('rejects payment method update for chip recurring subscription', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'chip_recurring_token' => 'chip-token-123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->postJson(route('donorportal.subscriptions.payment-method.update', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]), [
        'payment_method_id' => 'pm_test_123',
    ])
        ->assertUnprocessable()
        ->assertJson([
            'error' => 'Updating payment method is not supported for CHIP recurring subscriptions.',
        ]);
});

it('rejects client secret request for local app-controlled subscription', function () {
    $subscription = ($this->createSubscription)($this->donor);

    ($this->authenticateAsDonor)($this->donor);

    $this->getJson(route('donorportal.subscriptions.payment-method.client-secret', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]))
        ->assertUnprocessable()
        ->assertJson([
            'error' => 'Updating payment method is not supported for this subscription.',
        ]);
});

it('rejects payment method update for local app-controlled subscription', function () {
    $subscription = ($this->createSubscription)($this->donor);

    ($this->authenticateAsDonor)($this->donor);

    $this->postJson(route('donorportal.subscriptions.payment-method.update', [
        'organization' => $this->organization,
        'subscription' => $subscription,
    ]), [
        'payment_method_id' => 'pm_test_123',
    ])
        ->assertUnprocessable()
        ->assertJson([
            'error' => 'Updating payment method is not supported for this subscription.',
        ]);
});

it('hides update card button for chip subscription on subscriptions page', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'chip_recurring_token' => 'chip-token-123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->get(route('donorportal.subscriptions', $this->organization))
        ->assertOk()
        ->assertDontSee('openPayment(\''.$subscription->public_id.'\')', false);
});

it('shows update card button for stripe subscription on subscriptions page', function () {
    $subscription = ($this->createSubscription)($this->donor, [
        'stripe_subscription_id' => 'sub_test_123',
    ]);

    ($this->authenticateAsDonor)($this->donor);

    $this->get(route('donorportal.subscriptions', $this->organization))
        ->assertOk()
        ->assertSee('openPayment(\''.$subscription->public_id.'\')', false);
});
