<?php

declare(strict_types=1);

use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function foreignDonationMissingBaseAmount(array $attributes = []): Donation
{
    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_'.fake()->unique()->bothify('????????????'),
    ]);
    $campaign = Campaign::factory()->for($organization)->create();

    return Donation::factory()->for($campaign)->for(Donor::factory())->create([
        'currency' => 'sgd',
        'base_amount' => null,
        'exchange_rate' => null,
        'stripe_fee' => 0,
        'stripe_payment_intent_id' => 'pi_'.fake()->unique()->bothify('????????????'),
        'status' => DonationStatus::Succeeded,
        ...$attributes,
    ]);
}

/**
 * A charge that never collected has no balance transaction, so Stripe holds no
 * exchange rate for it and there is nothing to resync. Asking anyway spent a
 * call per donation and still reported every one of them as synced.
 */
it('resyncs only the foreign donations that actually collected', function () {
    $succeeded = foreignDonationMissingBaseAmount();
    foreignDonationMissingBaseAmount(['status' => DonationStatus::Failed]);
    foreignDonationMissingBaseAmount(['status' => DonationStatus::Pending]);

    $account = $succeeded->campaign->organization->stripe_account_id;

    mock(SyncDonationStripeDetails::class)
        ->shouldReceive('sync')
        ->once()
        ->withArgs(function (Donation $synced, mixed $paymentIntent, array $stripeOptions) use ($succeeded, $account) {
            return $synced->is($succeeded)
                && $paymentIntent === null
                && $stripeOptions === ['stripe_account' => $account];
        })
        ->andReturn([]);

    $exitCode = Artisan::call('app:resync-foreign-donation-exchange-rates');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('1 foreign donation(s)');
});

it('has nothing to do when every unconverted donation failed', function () {
    foreignDonationMissingBaseAmount(['status' => DonationStatus::Failed]);
    foreignDonationMissingBaseAmount(['status' => DonationStatus::Pending]);

    mock(SyncDonationStripeDetails::class)->shouldNotReceive('sync');

    $exitCode = Artisan::call('app:resync-foreign-donation-exchange-rates');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('No foreign donations with missing base_amount found.');
});

it('lists only what it would resync in a dry run', function () {
    $succeeded = foreignDonationMissingBaseAmount()->refresh();
    $failed = foreignDonationMissingBaseAmount(['status' => DonationStatus::Failed])->refresh();

    mock(SyncDonationStripeDetails::class)->shouldNotReceive('sync');

    Artisan::call('app:resync-foreign-donation-exchange-rates', ['--dry-run' => true]);

    expect(Artisan::output())
        ->toContain($succeeded->public_id)
        ->not->toContain($failed->public_id);
});

it('leaves donations already carrying a base amount alone', function () {
    foreignDonationMissingBaseAmount(['base_amount' => 320.00, 'exchange_rate' => 3.20]);

    mock(SyncDonationStripeDetails::class)->shouldNotReceive('sync');

    $exitCode = Artisan::call('app:resync-foreign-donation-exchange-rates');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('No foreign donations with missing base_amount found.');
});
