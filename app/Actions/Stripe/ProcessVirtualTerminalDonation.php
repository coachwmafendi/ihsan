<?php

namespace App\Actions\Stripe;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
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

        $feeCoverAmount = $coverFee ? DonationFeeEstimator::estimate($amount, $currency, 'stripe') : 0.0;
        $chargedAmount = $amount + $feeCoverAmount;

        $campaign = Campaign::query()
            ->where('id', $campaignId)
            ->where('organization_id', $organization->getKey())
            ->firstOrFail();

        $donor = $this->resolveOrCreateDonor($firstName, $lastName, $email);

        $stripeOptions = $organization->stripe_account_id
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        try {
            if (! $donor->stripe_customer_id) {
                $customerParams = [
                    'name' => trim(($donor->first_name ?? '').' '.($donor->last_name ?? '')),
                    'email' => $donor->email,
                    'metadata' => StripeMetadata::forDonorCustomer(
                        donor: $donor,
                        organization: $organization,
                        source: 'virtual_terminal_donation',
                    ),
                ];

                $address = StripeMetadata::customerAddress($donor);
                if ($address !== null) {
                    $customerParams['address'] = $address;
                }

                $locale = StripeMetadata::customerLocale($donor);
                if ($locale !== null) {
                    $customerParams['preferred_locales'] = $locale;
                }

                $customer = Customer::create($customerParams, $stripeOptions);

                $donor->update(['stripe_customer_id' => $customer->id]);
            }

            $params = [
                'amount' => (int) round($chargedAmount * 100),
                'currency' => strtolower($currency),
                'customer' => $donor->stripe_customer_id,
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
                $feePercent = (float) config('services.stripe.processing_fee_percent', 2.5);
                $params['application_fee_amount'] = (int) round($params['amount'] * $feePercent / 100);
            }

            $params['metadata'][StripeMetadata::key('platform_fee_amount')] = (string) ($params['application_fee_amount'] ?? 0);

            $paymentIntent = PaymentIntent::create($params, $stripeOptions);
        } catch (CardException $e) {
            throw new \RuntimeException('Card declined: '.$e->getMessage());
        } catch (InvalidRequestException $e) {
            throw new \RuntimeException('Invalid payment request. Please check your details.');
        } catch (ApiErrorException $e) {
            throw new \RuntimeException('Payment service error. Please try again.');
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

        $mailable = new DonationReceipt($donation);

        Mail::to($donor->email)->queue($mailable);

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $organization,
            donation: $donation,
        );

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
