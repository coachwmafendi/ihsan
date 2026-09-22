<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Models\Organization;
use App\Support\DomainName;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Reads back what Stripe thinks of an organization's registered checkout
 * domains. Registering a domain is not the same as it working: Stripe fetches
 * a verification file from the site afterwards, and a domain that fails that
 * check silently drops the wallet buttons from the Payment Element. Without
 * this the organiser has no way to tell a working domain from a broken one.
 *
 * Statuses are scoped to the connected account and to the API key's mode, the
 * same as registration.
 *
 * @phpstan-type DomainStatus array{apple_pay: string, google_pay: string, error: string|null}
 */
class FetchPaymentMethodDomainStatuses
{
    /**
     * Verification state barely moves once a domain is registered, and the two
     * things that do change it - saving the list, and Recheck wallets - both
     * bypass the cache, so a short window only bought repeated Stripe calls.
     */
    private const CacheMinutes = 60;

    public function __construct(private ?StripeClient $stripe = null) {}

    /**
     * @return array<string, DomainStatus> keyed by normalized domain
     */
    public function fetch(Organization $organization, bool $fresh = false): array
    {
        if (blank($organization->stripe_account_id)) {
            return [];
        }

        $key = $this->cacheKey($organization);

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(self::CacheMinutes), function () use ($organization): array {
            $statuses = $this->load($organization);

            $this->persist($organization, $statuses);

            return $statuses;
        });
    }

    /**
     * Keep the answer on the organization as well as in the cache.
     *
     * The checkout has to know whether the embedding site can carry a wallet,
     * and it cannot wait on a Stripe call to find out. Reading a stored answer
     * costs nothing, and a stale one is only ever as old as the last time
     * anyone opened the settings page or ran a registration.
     *
     * @param  array<string, DomainStatus>  $statuses
     */
    private function persist(Organization $organization, array $statuses): void
    {
        $organization->refresh();

        $settings = $organization->settings ?? [];

        $active = collect($statuses)
            ->filter(fn (array $status): bool => $status['apple_pay'] === 'active' && $status['google_pay'] === 'active')
            ->keys()
            ->values()
            ->all();

        if (($settings['wallet_verified_domains'] ?? null) === $active) {
            return;
        }

        $settings['wallet_verified_domains'] = $active;

        $organization->update(['settings' => $settings]);
    }

    /**
     * What is already cached, without ever reaching for Stripe.
     *
     * The settings page defers the fetch so a cold read cannot hold up the
     * first paint, but that deferral costs a second round trip on every visit -
     * including the overwhelming majority where the answer was sitting in the
     * cache all along. This lets the page paint the badges outright when it is.
     *
     * @return array<string, DomainStatus>|null null when nothing is cached
     */
    public function cached(Organization $organization): ?array
    {
        if (blank($organization->stripe_account_id)) {
            return null;
        }

        /** @var array<string, DomainStatus>|null */
        return Cache::get($this->cacheKey($organization));
    }

    public function forget(Organization $organization): void
    {
        Cache::forget($this->cacheKey($organization));
    }

    /**
     * @return array<string, DomainStatus>
     */
    private function load(Organization $organization): array
    {
        $stripe = $this->stripe ?? new StripeClient(config('services.stripe.secret'));

        try {
            $domains = $stripe->paymentMethodDomains->all(
                ['limit' => 100],
                ['stripe_account' => $organization->stripe_account_id],
            );
        } catch (ApiErrorException $e) {
            Log::warning('Failed to read Stripe payment method domains', [
                'organization_id' => $organization->id,
                'stripe_account_id' => $organization->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $statuses = [];

        foreach ($domains->data as $domain) {
            $statuses[DomainName::normalize((string) $domain->domain_name)] = [
                'apple_pay' => (string) ($domain->apple_pay->status ?? 'inactive'),
                'google_pay' => (string) ($domain->google_pay->status ?? 'inactive'),
                'error' => $this->firstError($domain),
            ];
        }

        return $statuses;
    }

    /**
     * Stripe reports the reason per wallet; the organiser only needs the first
     * one, since a domain that fails verification fails it for both.
     */
    private function firstError(object $domain): ?string
    {
        foreach (['apple_pay', 'google_pay'] as $wallet) {
            $message = $domain->{$wallet}->status_details->error_message ?? null;

            if (filled($message)) {
                return (string) $message;
            }
        }

        return null;
    }

    private function cacheKey(Organization $organization): string
    {
        return 'stripe:payment-method-domains:'.$organization->stripe_account_id;
    }
}
