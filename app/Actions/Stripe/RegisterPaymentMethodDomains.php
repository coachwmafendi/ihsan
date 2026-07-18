<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Registers an organization's checkout domains as Stripe payment method domains
 * on its connected account, so wallet buttons (Apple Pay / Google Pay / Link)
 * appear in the embedded Payment Element.
 *
 * Registration is scoped to the connected account and to the API key's mode
 * (test vs live); switching modes requires re-running this for every org.
 */
class RegisterPaymentMethodDomains
{
    public function __construct(private ?StripeClient $stripe = null) {}

    /**
     * Register the platform checkout domain plus the org's allowed domains.
     *
     * @return array<int, string> the domains that were registered (or revalidated)
     */
    public function register(Organization $organization): array
    {
        if (blank($organization->stripe_account_id)) {
            return [];
        }

        $stripe = $this->stripe ?? new StripeClient(config('services.stripe.secret'));
        $account = ['stripe_account' => $organization->stripe_account_id];

        $registered = [];

        foreach ($this->domainsFor($organization) as $domain) {
            try {
                $existing = $stripe->paymentMethodDomains->all(
                    ['domain_name' => $domain, 'limit' => 1],
                    $account,
                );

                if (count($existing->data) > 0) {
                    // Already registered; revalidate so a previously failed domain re-checks.
                    $stripe->paymentMethodDomains->validate($existing->data[0]->id, [], $account);
                } else {
                    $stripe->paymentMethodDomains->create(['domain_name' => $domain], $account);
                }

                $registered[] = $domain;
            } catch (ApiErrorException $e) {
                Log::warning('Failed to register Stripe payment method domain', [
                    'organization_id' => $organization->id,
                    'stripe_account_id' => $organization->stripe_account_id,
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $registered;
    }

    /**
     * Platform checkout domain (Payment Element lives here) plus org allowed domains.
     *
     * @return array<int, string>
     */
    private function domainsFor(Organization $organization): array
    {
        $panelDomain = (string) config('app.app_panel_domain');

        $allowed = (array) ($organization->settings['allowed_domains'] ?? []);

        return collect([$panelDomain, ...$allowed])
            ->map(fn ($domain): string => $this->normalizeDomain((string) $domain))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $host = parse_url($domain, PHP_URL_HOST);

        if ($host) {
            $domain = $host;
        }

        $domain = strtolower($domain);

        return str_starts_with($domain, 'www.') ? substr($domain, 4) : $domain;
    }
}
