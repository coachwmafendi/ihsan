<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Actions\Stripe\CreateRecurringSubscription;
use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Campaign;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Stripe::setApiKey(config('services.stripe.secret'));

$organizationId = 15;
$campaignId = 16;
$elementId = 10;

$campaign = Campaign::find($campaignId);

$stripeOptions = ['stripe_account' => 'acct_1JoNRrCwYRUwY5lY'];

const TEST_PAYMENT_METHOD_ID = 'pm_card_visa';

function makeDonation(float $amount, DonationType $type, int $index, string $frequencyLabel, array $stripeOptions, int $campaignId, int $elementId): void
{
    $email = "test.donor.{$index}." . strtolower(str_replace(' ', '', $frequencyLabel)) . "@example.com";
    $name = "Test Donor {$index} ({$frequencyLabel})";

    echo "[{$index}] Creating donor: {$email}\n";

    $donor = Donor::query()->updateOrCreate(
        ['email' => $email],
        ['name' => $name, 'phone' => '0123456789'],
    );

    echo "[{$index}] Creating donation: RM{$amount} ({$type->value})\n";

    $donation = Donation::query()->create([
        'campaign_id' => $campaignId,
        'donor_id' => $donor->getKey(),
        'gross_amount' => $amount,
        'stripe_fee' => 0,
        'processing_fee' => 0,
        'net_amount' => $amount,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => $type,
        'donor_message' => "Test donation {$index} - {$amount} {$type->value}",
        'is_anonymous' => false,
        'utm_params' => [
            'element_id' => $elementId,
            'frequency' => $type === DonationType::OneTime ? 'one_time' : 'monthly',
        ],
    ]);

    echo "[{$index}] Creating PaymentIntent...\n";

    $paymentIntent = StripePaymentIntent::create([
        'amount' => (int) ($amount * 100),
        'currency' => 'myr',
        'automatic_payment_methods' => [
            'enabled' => true,
            'allow_redirects' => 'never',
        ],
        'receipt_email' => $email,
        'description' => "Wakaf Pembangunan Pusat Tahfiz Annur",
        'metadata' => [
            'donation_id' => (string) $donation->getKey(),
            'donor_name' => $name,
            'donor_email' => $email,
            'donor_phone' => '0123456789',
            'campaign_id' => (string) $campaignId,
            'organization_id' => (string) 15,
        ],
        'application_fee_amount' => (int) round($amount * 2.5 / 100 * 100),
    ], $stripeOptions);

    if ($type === DonationType::Recurring) {
        StripePaymentIntent::update($paymentIntent->id, [
            'setup_future_usage' => 'off_session',
        ], $stripeOptions);
        // Refresh to get updated object
        $paymentIntent = StripePaymentIntent::retrieve(['id' => $paymentIntent->id, 'expand' => ['latest_charge.balance_transaction']], $stripeOptions);
    }

    $donation->update(['stripe_payment_intent_id' => $paymentIntent->id]);

    echo "[{$index}] Confirming PaymentIntent...\n";

    // Confirm with test payment method
    $confirmed = $paymentIntent->confirm([
        'payment_method' => TEST_PAYMENT_METHOD_ID,
    ], $stripeOptions);

    echo "[{$index}] PaymentIntent status: {$confirmed->status}\n";

    if ($confirmed->status !== 'succeeded') {
        echo "[{$index}] FAILED! Status: {$confirmed->status}\n";
        $donation->update(['status' => DonationStatus::Failed]);
        return;
    }

    // Sync Stripe details
    $synced = app(SyncDonationStripeDetails::class)->sync($donation, $confirmed, $stripeOptions);

    $donation->update(['status' => DonationStatus::Succeeded]);
    $donation->campaign()->increment('collected_amount', (float) $donation->gross_amount);

    if ($type === DonationType::Recurring) {
        echo "[{$index}] Creating subscription...\n";
        $subscription = app(CreateRecurringSubscription::class)->create($donation, $confirmed, $stripeOptions);
        $donation->update(['subscription_id' => $subscription->getKey()]);
        $subscription->increment('payment_count');
        echo "[{$index}] Subscription created: {$subscription->id}\n";
    }

    echo "[{$index}] DONE! Donation #{$donation->id} - RM{$amount} {$type->value}\n";
    echo "-----------------------------------------------------\n\n";
}

echo "==========================================\n";
echo " MAKING 10 ONE-OFF DONATIONS OF RM321\n";
echo "==========================================\n\n";

for ($i = 1; $i <= 10; $i++) {
    makeDonation(321, DonationType::OneTime, $i, 'OneTime', $stripeOptions, $campaignId, $elementId);
}

echo "\n\n==========================================\n";
echo " MAKING 10 MONTHLY DONATIONS OF RM66\n";
echo "==========================================\n\n";

for ($i = 11; $i <= 20; $i++) {
    makeDonation(66, DonationType::Recurring, $i, 'Monthly', $stripeOptions, $campaignId, $elementId);
}

echo "\n\nALL DONE!\n";
