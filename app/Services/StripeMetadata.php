<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

class StripeMetadata
{
    public static function key(string $name): string
    {
        $prefix = str(config('app.name'))
            ->lower()
            ->slug('_')
            ->toString();

        return "{$prefix}_{$name}";
    }

    /**
     * Build metadata for a Stripe Customer representing a donor.
     */
    public static function forDonorCustomer(?Donor $donor, Organization $organization, string $source): array
    {
        $metadata = [
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('source') => $source,
            self::key('environment') => config('app.env'),
        ];

        if ($donor !== null) {
            $metadata[self::key('donor_id')] = (string) $donor->getKey();
            $metadata[self::key('donor_public_id')] = $donor->public_id ?? '';

            if (filled($donor->country)) {
                $metadata[self::key('donor_country')] = $donor->country;
            }

            if (filled($donor->locale)) {
                $metadata[self::key('donor_locale')] = $donor->locale;
            }
        }

        return $metadata;
    }

    /**
     * Build metadata for a Stripe Customer representing an organization
     * (e.g. platform billing invoices).
     */
    public static function forOrganizationCustomer(Organization $organization, string $source = 'platform_invoice'): array
    {
        return [
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('source') => $source,
            self::key('environment') => config('app.env'),
        ];
    }

    /**
     * Build metadata for a Stripe PaymentIntent created from a donation.
     */
    public static function forPaymentIntent(Donation $donation, Organization $organization, ?Element $element = null): array
    {
        $metadata = [
            self::key('donation_id') => (string) $donation->getKey(),
            self::key('donation_public_id') => $donation->public_id ?? '',
            self::key('donor_id') => (string) $donation->donor_id,
            self::key('donor_public_id') => $donation->donor?->public_id ?? '',
            self::key('donor_name') => $donation->donor?->name ?? '',
            self::key('donor_email') => $donation->donor?->email ?? '',
            self::key('donor_phone') => $donation->donor?->phone ?? '',
            self::key('campaign_id') => (string) $donation->campaign_id,
            self::key('campaign_public_id') => $donation->campaign?->public_id ?? '',
            self::key('campaign_name') => $donation->campaign?->title ?? '',
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('organization_name') => $organization->name,
            self::key('donation_type') => $donation->type->value,
            self::key('cover_fee') => ($donation->donor_fee_covered ?? 0) > 0 ? 'true' : 'false',
            self::key('fee_cover_amount') => (string) ($donation->donor_fee_covered ?? 0),
            self::key('source') => self::paymentIntentSource($donation),
            self::key('environment') => config('app.env'),
        ];

        if ($element !== null) {
            $metadata[self::key('element_id')] = (string) $element->getKey();
            $metadata[self::key('element_public_id')] = $element->public_id ?? '';
            $metadata[self::key('element_name')] = $element->name;
        }

        return $metadata;
    }

    /**
     * Resolve the source value for a PaymentIntent based on the donation source.
     */
    public static function paymentIntentSource(Donation $donation): string
    {
        $source = $donation->source ?? 'checkout_modal';

        if (in_array($source, ['campaign_page', 'checkout_modal', 'virtual_terminal', 'element'], true)) {
            return $source;
        }

        return 'checkout_modal';
    }

    /**
     * Build metadata for a Stripe Subscription created from a recurring donation.
     */
    public static function forRecurringSubscription(
        Donation $donation,
        Organization $organization,
        ?Element $element = null,
        ?Subscription $subscription = null,
    ): array {
        $metadata = [
            self::key('donation_id') => (string) $donation->getKey(),
            self::key('donation_public_id') => $donation->public_id ?? '',
            self::key('donor_id') => (string) $donation->donor_id,
            self::key('donor_public_id') => $donation->donor?->public_id ?? '',
            self::key('donor_name') => $donation->donor?->name ?? '',
            self::key('donor_email') => $donation->donor?->email ?? '',
            self::key('donor_phone') => $donation->donor?->phone ?? '',
            self::key('campaign_id') => (string) $donation->campaign_id,
            self::key('campaign_public_id') => $donation->campaign?->public_id ?? '',
            self::key('campaign_name') => $donation->campaign?->title ?? '',
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('organization_name') => $organization->name,
            self::key('recurring_donation') => 'true',
            self::key('donation_type') => $donation->type->value,
            self::key('amount') => (string) $donation->gross_amount,
            self::key('currency') => $donation->currency,
            self::key('interval') => 'month',
            self::key('cover_fee') => ($donation->donor_fee_covered ?? 0) > 0 ? 'true' : 'false',
            self::key('fee_cover_amount') => (string) ($donation->donor_fee_covered ?? 0),
            self::key('source') => self::paymentIntentSource($donation),
            self::key('flow') => 'recurring_subscription',
            self::key('environment') => config('app.env'),
        ];

        if ($element !== null) {
            $metadata[self::key('element_id')] = (string) $element->getKey();
            $metadata[self::key('element_public_id')] = $element->public_id ?? '';
            $metadata[self::key('element_name')] = $element->name;
        }

        if ($subscription !== null) {
            $metadata[self::key('subscription_id')] = (string) $subscription->getKey();
            $metadata[self::key('subscription_public_id')] = $subscription->public_id ?? '';
        }

        return $metadata;
    }

    /**
     * Build metadata for a Stripe Subscription created from the virtual terminal.
     */
    public static function forVirtualTerminalSubscription(
        Campaign $campaign,
        Donor $donor,
        Organization $organization,
        float $amount,
        string $currency,
        ?Subscription $subscription = null,
    ): array {
        $metadata = [
            self::key('donor_id') => (string) $donor->getKey(),
            self::key('donor_public_id') => $donor->public_id ?? '',
            self::key('donor_name') => $donor->name,
            self::key('donor_email') => $donor->email,
            self::key('campaign_id') => (string) $campaign->getKey(),
            self::key('campaign_public_id') => $campaign->public_id ?? '',
            self::key('campaign_name') => $campaign->title,
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('organization_name') => $organization->name,
            self::key('recurring_donation') => 'true',
            self::key('amount') => (string) $amount,
            self::key('currency') => strtolower($currency),
            self::key('interval') => 'month',
            self::key('source') => 'virtual_terminal',
            self::key('flow') => 'virtual_terminal_subscription',
            self::key('environment') => config('app.env'),
        ];

        if ($subscription !== null) {
            $metadata[self::key('subscription_id')] = (string) $subscription->getKey();
            $metadata[self::key('subscription_public_id')] = $subscription->public_id ?? '';
        }

        return $metadata;
    }

    /**
     * Build a Stripe Customer preferred_locales array from donor locale.
     */
    public static function customerLocale(?Donor $donor): ?array
    {
        if ($donor === null || blank($donor->locale)) {
            return null;
        }

        return [$donor->locale];
    }

    /**
     * Build a Stripe Customer address array from donor address fields.
     */
    public static function customerAddress(?Donor $donor): ?array
    {
        if ($donor === null) {
            return null;
        }

        $address = [
            'line1' => $donor->address_line1 ?? '',
            'line2' => $donor->address_line2 ?? '',
            'city' => $donor->address_city ?? '',
            'state' => $donor->address_state ?? '',
            'postal_code' => $donor->address_postal_code ?? '',
            'country' => $donor->country ?? '',
        ];

        if (collect($address)->every(fn (string $value): bool => $value === '')) {
            return null;
        }

        return $address;
    }

    /**
     * Build a Stripe Customer address array from organization address fields.
     */
    public static function organizationAddress(Organization $organization): ?array
    {
        $address = [
            'line1' => $organization->address_line_1 ?? '',
            'line2' => $organization->address_line_2 ?? '',
            'city' => $organization->city ?? '',
            'state' => $organization->state ?? '',
            'postal_code' => $organization->postcode ?? '',
            'country' => $organization->country ?? '',
        ];

        if (collect($address)->every(fn (string $value): bool => $value === '')) {
            return null;
        }

        return $address;
    }

    /**
     * Build metadata for a Stripe platform invoice sent to an organization.
     */
    public static function forPlatformInvoice(Organization $organization, Carbon $period): array
    {
        return [
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('organization_name') => $organization->name,
            self::key('period') => $period->format('Y-m-d'),
            self::key('type') => 'processing_fees',
            self::key('environment') => config('app.env'),
        ];
    }

    /**
     * Build metadata to update a Stripe Subscription when donor details change.
     */
    public static function forDonorUpdate(Donor $donor): array
    {
        $metadata = [
            self::key('donor_name') => $donor->name,
            self::key('donor_email') => $donor->email,
            self::key('donor_public_id') => $donor->public_id ?? '',
        ];

        if (filled($donor->phone)) {
            $metadata[self::key('donor_phone')] = $donor->phone;
        }

        return $metadata;
    }

    /**
     * Build metadata for a Stripe Product representing a campaign subscription item.
     */
    public static function forProduct(Campaign $campaign, Organization $organization, string $type = 'donation'): array
    {
        return [
            self::key('campaign_id') => (string) $campaign->getKey(),
            self::key('campaign_public_id') => $campaign->public_id ?? '',
            self::key('campaign_name') => $campaign->title,
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('organization_name') => $organization->name,
            self::key('type') => $type,
            self::key('environment') => config('app.env'),
        ];
    }

    /**
     * Build metadata for a Stripe Price representing a subscription billing tier.
     */
    public static function forPrice(
        Campaign $campaign,
        Organization $organization,
        float $amount,
        string $currency,
        string $interval,
        string $type = 'donation',
    ): array {
        return [
            self::key('campaign_id') => (string) $campaign->getKey(),
            self::key('campaign_public_id') => $campaign->public_id ?? '',
            self::key('organization_id') => (string) $organization->getKey(),
            self::key('organization_public_id') => $organization->public_id ?? '',
            self::key('amount') => (string) $amount,
            self::key('currency') => strtolower($currency),
            self::key('interval') => $interval,
            self::key('type') => $type,
            self::key('environment') => config('app.env'),
        ];
    }
}
