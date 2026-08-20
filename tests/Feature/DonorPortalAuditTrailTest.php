<?php

declare(strict_types=1);

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogQuery;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

/**
 * A supporter managing their own plan used to leave no trace at all, so an
 * admin asking "who cancelled this?" had nothing to go on.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->donor = Donor::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create();

    $this->subscription = Subscription::factory()->for($this->campaign)->for($this->donor)->create([
        'stripe_subscription_id' => null,
        'stripe_price_id' => null,
        'chip_recurring_token' => null,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'amount' => 30.00,
        'currency' => 'myr',
        'next_charge_at' => now()->addDay(),
    ]);

    $this->asDonor = fn () => test()->withSession([
        'donor_id' => $this->donor->getKey(),
        'organization_id' => $this->organization->getKey(),
    ]);
});

function latestSubscriptionActivity(string $event): ?Activity
{
    return Activity::query()->where('event', $event)->latest('id')->first();
}

it('records a plan the supporter cancelled themselves', function () {
    ($this->asDonor)()
        ->post(route('donorportal.subscriptions.cancel', [$this->organization, $this->subscription]))
        ->assertRedirect();

    $activity = latestSubscriptionActivity('subscription.cancelled');

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('initiator'))->toBe('donor')
        ->and($activity->properties->get('source'))->toBe('donor_portal')
        ->and($activity->description)->toContain($this->subscription->public_id);
});

it('records a plan the supporter paused and resumed', function () {
    ($this->asDonor)()
        ->post(route('donorportal.subscriptions.pause', [$this->organization, $this->subscription]))
        ->assertRedirect();

    expect(latestSubscriptionActivity('subscription.paused')?->properties->get('source'))->toBe('donor_portal');

    ($this->asDonor)()
        ->post(route('donorportal.subscriptions.resume', [$this->organization, $this->subscription]))
        ->assertRedirect();

    expect(latestSubscriptionActivity('subscription.resumed')?->properties->get('source'))->toBe('donor_portal');
});

it('records the amount a supporter changed their plan to', function () {
    ($this->asDonor)()
        ->post(route('donorportal.subscriptions.change-amount', [$this->organization, $this->subscription]), [
            'new_amount' => 75,
        ]);

    $activity = latestSubscriptionActivity('subscription.updated');

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('source'))->toBe('donor_portal')
        ->and($activity->description)->toContain('30.00')
        ->and($activity->description)->toContain('75.00');
});

it('shows what the supporter did in the organization audit log', function () {
    ($this->asDonor)()
        ->post(route('donorportal.subscriptions.cancel', [$this->organization, $this->subscription]))
        ->assertRedirect();

    $user = User::factory()->for($this->organization)->create();

    actingAs($user)
        ->get('https://app.example.test/audit-log')
        ->assertOk()
        ->assertSee('Donor portal')
        ->assertSee($this->subscription->public_id);

    // The initiator filter has to reach it: there is no causer to match on.
    $descriptions = AuditLogQuery::forOrganization($this->organization, ['initiator' => 'donor'])
        ->pluck('description');

    expect($descriptions)->toHaveCount(1)
        ->and($descriptions->first())->toContain($this->subscription->public_id);
});
