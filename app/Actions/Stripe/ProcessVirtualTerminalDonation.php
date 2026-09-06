<?php

namespace App\Actions\Stripe;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SyncDonationStripeDetailsJob;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Services\DonationFeeEstimator;
use App\Services\StripeMetadata;
use Illuminate\Support\Facades\Mail;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class ProcessVirtualTerminalDonation
{
    public function handle(
        int $campaignId,
        float $amount,
        string $firstName,
        string $lastName,
        string $email,
        Organization $organization,
        string $currency = 'myr',
        ?string $savedCardId = null,
        ?string $paymentMethodId = null,
        string $source = 'virtual_terminal',
        bool $coverFee = false,
    ): Donation {
        Stripe::setApiKey(config('services.stripe.secret'));

        $feeCoverAmount = $coverFee
            ? DonationFeeEstimator::estimate(
                $amount,
                $currency,
                'stripe',
                $organization->processing_fee_override !== null ? (float) $organization->processing_fee_override : null,
            )
            : 0.0;
        $chargedAmount = $amount + $feeCoverAmount;

        $campaign = Campaign::query()
            ->where('id', $campaignId)
            ->where('organization_id', $organization->getKey())
            ->firstOrFail();

        $donor = $this->resolveOrCreateDonor($firstName, $lastName, $email);

        $stripeOptions = $organization->stripeOptions();

        try {
            $customerId = app(ResolveDonorStripeCustomer::class)
                ->resolve($donor, $organization, 'virtual_terminal_donation');

            // Attach a freshly tokenised card to the customer before charging.
            // Stripe rejects re-using an unattached PaymentMethod, so an off-session
            // confirm needs the card attached first.
            if ($paymentMethodId) {
                PaymentMethod::retrieve($paymentMethodId, $stripeOptions)
                    ->attach(['customer' => $customerId], $stripeOptions);
            }

            $params = [
                'amount' => (int) round($chargedAmount * 100),
                'currency' => strtolower($currency),
                'customer' => $customerId,
                'description' => (string) str($campaign->title)->limit(200),
                'metadata' => [
                    StripeMetadata::key('campaign_id') => (string) $campaign->getKey(),
                    StripeMetadata::key('campaign_public_id') => $campaign->public_id ?? '',
                    StripeMetadata::key('campaign_name') => $campaign->title,
                    StripeMetadata::key('donor_id') => (string) $donor->getKey(),
                    StripeMetadata::key('donor_public_id') => $donor->public_id ?? '',
                    StripeMetadata::key('donor_name') => $donor->name,
                    StripeMetadata::key('donor_email') => $donor->email,
                    StripeMetadata::key('organization_id') => (string) $organization->getKey(),
                    StripeMetadata::key('organization_public_id') => $organization->public_id ?? '',
                    StripeMetadata::key('organization_name') => $organization->name,
                    StripeMetadata::key('source') => 'virtual_terminal',
                    StripeMetadata::key('environment') => config('app.env'),
                ],
                'receipt_email' => $donor->email,
            ];

            if ($paymentMethodId) {
                $params['payment_method'] = $paymentMethodId;
                $params['off_session'] = true;
                $params['confirm'] = true;
            } elseif ($savedCardId) {
                $params['payment_method'] = $savedCardId;
                $params['off_session'] = true;
                $params['confirm'] = true;
            } else {
                $params['automatic_payment_methods'] = ['enabled' => true];
            }

            if ($organization->stripe_account_id && $organization->fee_collection_method === 'upfront') {
                $feePercent = (float) ($organization->processing_fee_override ?? config('services.stripe.processing_fee_percent', 2.5));
                // The fee cover pays the fees; it isn't part of the donation.
                $params['application_fee_amount'] = (int) round($amount * 100 * $feePercent / 100);
            }

            $params['metadata'][StripeMetadata::key('platform_fee_amount')] = (string) ($params['application_fee_amount'] ?? 0);

            $paymentIntent = PaymentIntent::create($params, $stripeOptions);
        } catch (CardException $e) {
            throw new \RuntimeException('Card declined: '.$e->getMessage(), previous: $e);
        } catch (InvalidRequestException $e) {
            throw new \RuntimeException('Invalid payment request: '.$e->getMessage(), previous: $e);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException('Payment service error: '.$e->getMessage(), previous: $e);
        }

        $donation = Donation::create([
            'campaign_id' => $campaign->getKey(),
            'donor_id' => $donor->getKey(),
            'source' => $source,
            'gross_amount' => $amount,
            'base_amount' => strtolower($currency) === 'myr' ? $amount : null,
            'donor_fee_covered' => $feeCoverAmount,
            'currency' => strtolower($currency),
            'base_currency' => 'myr',
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::OneTime,
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);

        // Populate the MYR-converted base amount, exchange rate, fees, payment
        // method details and saved card from the settled charge, matching the
        // webhook-driven sync used by the public donation flow.
        try {
            app(SyncDonationStripeDetails::class)->sync($donation, $paymentIntent, $stripeOptions);
            $donation->refresh();
        } catch (\Throwable $e) {
            report($e);
        }

        // Re-sync once the balance transaction has settled so the MYR base amount
        // and exchange rate are captured (not always available immediately after
        // an off-session confirm).
        SyncDonationStripeDetailsJob::dispatch($donation->getKey())->delay(now()->addMinutes(2));

        $mailable = new DonationReceipt($donation);

        Mail::to($donor->email)->queue($mailable);

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $organization,
            donation: $donation,
        );

        // Notify the organisation of the new donation, matching the public flow.
        SendNewDonationNotification::dispatch($donation)->delay(now()->addMinutes(5));
        SendLargeDonationNotification::dispatch($donation)->delay(now()->addMinutes(5));

        return $donation;
    }

    private function resolveOrCreateDonor(
        string $firstName,
        string $lastName,
        string $email,
    ): Donor {
        // Donors are keyed by their globally-unique email, matching the public donation form.
        return Donor::updateOrCreate(
            ['email' => str($email)->lower()->toString()],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
        );
    }
}
