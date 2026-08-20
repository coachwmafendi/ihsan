<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Jobs\SendSubscriptionAmountChangedNotification;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Mail\SupporterSubscriptionAmountChangedNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends donor and admin amount changed notifications by default', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 75.00,
        'currency' => 'myr',
    ]);

    (new SendSubscriptionAmountChangedNotification($subscription, 50.00))->handle();

    Mail::assertQueued(SupporterSubscriptionAmountChangedNotification::class, 1);
    Mail::assertQueued(SubscriptionAmountChangedNotification::class, function (SubscriptionAmountChangedNotification $mail) use ($admin) {
        return $mail->hasTo($admin->email);
    });
});

it('does not send donor amount changed notification when donor toggle is disabled', function () {
    Mail::fake();

    $organization = Organization::factory()->create([
        'settings' => ['notify_supporter_subscription_amount_changed' => false],
    ]);
    $admin = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 75.00,
        'currency' => 'myr',
    ]);

    (new SendSubscriptionAmountChangedNotification($subscription, 50.00))->handle();

    Mail::assertNotQueued(SupporterSubscriptionAmountChangedNotification::class);
    Mail::assertQueued(SubscriptionAmountChangedNotification::class, function (SubscriptionAmountChangedNotification $mail) use ($admin) {
        return $mail->hasTo($admin->email);
    });
});

it('does not send admin amount changed notification when admin toggle is disabled', function () {
    Mail::fake();

    $organization = Organization::factory()->create([
        'settings' => ['notify_subscription_amount_changed' => false],
    ]);
    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 75.00,
        'currency' => 'myr',
    ]);

    (new SendSubscriptionAmountChangedNotification($subscription, 50.00))->handle();

    Mail::assertQueued(SupporterSubscriptionAmountChangedNotification::class, 1);
    Mail::assertNotQueued(SubscriptionAmountChangedNotification::class);
});
