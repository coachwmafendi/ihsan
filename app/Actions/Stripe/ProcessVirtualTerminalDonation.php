<?php

namespace App\Actions\Stripe;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
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
                $customer = Customer::create([
                    'name' => $donor->name,
                    'email' => $donor->email,
                    'metadata' => [
                        'donor_id' => (string) $donor->getKey(),
                        'organization_id' => (string) $organization->getKey(),
                    ],
                ], $stripeOptions);

                $donor->update(['stripe_customer_id' => $customer->id]);
            }

            $params = [
                'amount' => (int) ($amount * 100),
                'currency' => 'myr',
                'customer' => $donor->stripe_customer_id,
                'description' => (string) str($campaign->title)->limit(200),
                'metadata' => [
                    'campaign_id' => (string) $campaign->getKey(),
                    'donor_public_id' => $donor->public_id,
                    'source' => 'virtual_terminal',
                    'organization_id' => (string) $organization->getKey(),
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
            'currency' => 'myr',
            'base_currency' => 'myr',
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::OneTime,
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);

        Mail::to($donor->email)->queue(new DonationReceipt($donation));

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
}
