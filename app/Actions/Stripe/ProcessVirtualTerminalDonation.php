<?php

namespace App\Actions\Stripe;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
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
    ): Donation {
        Stripe::setApiKey(config('services.stripe.secret'));

        $campaign = Campaign::query()
            ->where('id', $campaignId)
            ->where('organization_id', $organization->getKey())
            ->firstOrFail();

        $donor = $this->resolveOrCreateDonor($firstName, $lastName, $email, $organization);

        $stripeOptions = $organization->stripe_account_id
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        try {
            if (! $donor->stripe_customer_id) {
                $customerParams = [
                    'name' => $donor->name,
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
                'amount' => (int) ($amount * 100),
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
            'base_amount' => $amount,
            'currency' => strtolower($currency),
            'base_currency' => 'myr',
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::OneTime,
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);

        // Sync payment method details to local cache
        $this->syncPaymentMethod($donor, $paymentIntent->payment_method, $stripeOptions);

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
        Organization $organization,
    ): Donor {
        $fullName = trim("{$firstName} {$lastName}");

        $donor = Donor::query()
            ->where('email', $email)
            ->whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $organization->getKey()))
            ->first();

        if ($donor) {
            return $donor;
        }

        return Donor::create([
            'name' => $fullName,
            'email' => $email,
        ]);
    }

    private function syncPaymentMethod(Donor $donor, ?string $stripePaymentMethodId, array $stripeOptions): void
    {
        if (! $stripePaymentMethodId || ! $donor->stripe_customer_id) {
            return;
        }

        // Skip if already cached
        if (DonorPaymentMethod::where('stripe_payment_method_id', $stripePaymentMethodId)->exists()) {
            return;
        }

        try {
            $pm = PaymentMethod::retrieve($stripePaymentMethodId, $stripeOptions);

            if ($pm->type !== 'card' || ! $pm->card) {
                return;
            }

            DonorPaymentMethod::create([
                'donor_id' => $donor->getKey(),
                'stripe_payment_method_id' => $pm->id,
                'brand' => ucfirst($pm->card->brand),
                'last4' => $pm->card->last4,
                'exp_month' => $pm->card->exp_month,
                'exp_year' => $pm->card->exp_year,
                'country' => $pm->card->country ?? null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
