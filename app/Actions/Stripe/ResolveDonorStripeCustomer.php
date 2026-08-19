<?php

namespace App\Actions\Stripe;

use App\Models\Donor;
use App\Models\DonorStripeCustomer;
use App\Models\Organization;
use App\Services\StripeMetadata;
use Stripe\Customer;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentMethod;
use Stripe\Stripe;

/**
 * Resolves the Stripe customer representing a donor inside one organization's
 * connected account.
 *
 * Stripe customers belong to a single account, so a donor supporting several
 * organizations needs a separate customer per account. The mapping table is the
 * source of truth; the legacy donors.stripe_customer_id column is only adopted
 * when it turns out to belong to the account being addressed.
 */
class ResolveDonorStripeCustomer
{
    public function resolve(Donor $donor, Organization $organization, string $source = 'donation_checkout'): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $mapped = $this->resolveExisting($donor, $organization);

        if ($mapped !== null) {
            return $mapped;
        }

        $stripeOptions = $organization->stripeOptions();

        $adopted = $this->adoptLegacyCustomer($donor, $organization, $stripeOptions);

        if ($adopted !== null) {
            return $adopted;
        }

        return $this->createCustomer($donor, $organization, $stripeOptions, $source);
    }

    /**
     * Resolve without ever creating a customer.
     *
     * Suitable for sync and read paths, which must not conjure a customer for a
     * donor who has never transacted with this organization, but should still
     * heal from the legacy column when it happens to point at this account.
     */
    public function resolveWithoutCreating(Donor $donor, Organization $organization): ?string
    {
        $mapped = $this->resolveExisting($donor, $organization);

        if ($mapped !== null) {
            return $mapped;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            return $this->adoptLegacyCustomer($donor, $organization, $organization->stripeOptions());
        } catch (\Throwable $e) {
            // Best effort only: a transient Stripe failure must not break the
            // caller, which treats a null customer as "nothing to sync".
            report($e);

            return null;
        }
    }

    /**
     * The customer already mapped to this donor/organization pair, if any.
     */
    public function resolveExisting(Donor $donor, Organization $organization): ?string
    {
        $customerId = DonorStripeCustomer::query()
            ->where('donor_id', $donor->getKey())
            ->where('organization_id', $organization->getKey())
            ->value('stripe_customer_id');

        return blank($customerId) ? null : $customerId;
    }

    /**
     * Recover the customer from a payment method already saved for this donor.
     *
     * A payment method is attached to exactly one customer inside one account,
     * so it identifies the customer authoritatively — which lets charges heal
     * themselves when the mapping has not been backfilled yet.
     */
    public function adoptFromPaymentMethod(Donor $donor, Organization $organization, string $paymentMethodId): ?string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $organization->stripeOptions());
        } catch (InvalidRequestException) {
            return null;
        }

        $customerId = $paymentMethod->customer ?? null;

        if (blank($customerId)) {
            return null;
        }

        $customerId = is_string($customerId) ? $customerId : $customerId->id;

        $this->rememberMapping($donor, $organization, $customerId);

        return $customerId;
    }

    /**
     * Claim the legacy donor-level customer id, but only when it really exists
     * on this account — otherwise it belongs to a different organization.
     *
     * @param  array{stripe_account?: string}  $stripeOptions
     */
    private function adoptLegacyCustomer(Donor $donor, Organization $organization, array $stripeOptions): ?string
    {
        if (blank($donor->stripe_customer_id)) {
            return null;
        }

        try {
            $customer = Customer::retrieve($donor->stripe_customer_id, $stripeOptions);
        } catch (InvalidRequestException) {
            return null;
        }

        if (($customer->deleted ?? false) === true) {
            return null;
        }

        $this->rememberMapping($donor, $organization, $customer->id);

        return $customer->id;
    }

    /**
     * @param  array{stripe_account?: string}  $stripeOptions
     */
    private function createCustomer(Donor $donor, Organization $organization, array $stripeOptions, string $source): string
    {
        if (blank($donor->email)) {
            throw new \RuntimeException('Cannot create a Stripe customer without a donor email.');
        }

        $customer = Customer::create($this->customerParams($donor, $organization, $source), $stripeOptions);

        $this->rememberMapping($donor, $organization, $customer->id);

        return $customer->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function customerParams(Donor $donor, Organization $organization, string $source): array
    {
        $params = [
            'email' => $donor->email,
            'metadata' => StripeMetadata::forDonorCustomer(
                donor: $donor,
                organization: $organization,
                source: $source,
            ),
        ];

        $name = trim(($donor->first_name ?? '').' '.($donor->last_name ?? ''));

        if ($name !== '') {
            $params['name'] = $name;
        }

        if (filled($donor->phone)) {
            $params['phone'] = $donor->phone;
        }

        $address = StripeMetadata::customerAddress($donor);

        if ($address !== null) {
            $params['address'] = $address;
        }

        $locale = StripeMetadata::customerLocale($donor);

        if ($locale !== null) {
            $params['preferred_locales'] = $locale;
        }

        return $params;
    }

    /**
     * Record a customer id discovered from an authoritative Stripe object.
     */
    public function remember(Donor $donor, Organization $organization, string $customerId): void
    {
        $this->rememberMapping($donor, $organization, $customerId);
    }

    private function rememberMapping(Donor $donor, Organization $organization, string $customerId): void
    {
        DonorStripeCustomer::query()->updateOrCreate(
            [
                'donor_id' => $donor->getKey(),
                'organization_id' => $organization->getKey(),
            ],
            ['stripe_customer_id' => $customerId],
        );

        // Keep the legacy column populated for donors that have never had one,
        // but never overwrite it — that overwrite is what broke cross-account
        // lookups in the first place.
        if (blank($donor->stripe_customer_id)) {
            $donor->update(['stripe_customer_id' => $customerId]);
        }
    }
}
