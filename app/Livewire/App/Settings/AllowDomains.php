<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Actions\Stripe\FetchPaymentMethodDomainStatuses;
use App\Actions\Stripe\RegisterPaymentMethodDomains;
use App\Jobs\RegisterStripePaymentMethodDomains;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Support\CheckoutDomains;
use App\Support\DomainName;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settings — Allowed Domains')]
class AllowDomains extends Component
{
    public const MAX_ALLOWED_DOMAINS = 10;

    public array $allowed_domains = [];

    /**
     * What Stripe says about each registered domain, keyed by domain.
     *
     * @var array<string, array{apple_pay: string, google_pay: string, error: string|null}>
     */
    public array $domain_statuses = [];

    public bool $statuses_loaded = false;

    public function mount(): void
    {
        $org = $this->organization();

        if (! $org) {
            return;
        }

        $settings = $org->settings ?? [];
        $allowedDomains = $settings['allowed_domains'] ?? null;

        if ($allowedDomains === null && $org->website_url) {
            $domain = $this->normalizeDomain($org->website_url);

            if ($domain !== '') {
                $allowedDomains = [$domain];
            }
        }

        $this->allowed_domains = $allowedDomains ?? [];
    }

    /**
     * Deferred so the Stripe round trip never holds up the first paint.
     */
    public function loadDomainStatuses(FetchPaymentMethodDomainStatuses $statuses): void
    {
        $org = $this->organization();

        $this->domain_statuses = $org ? $statuses->fetch($org) : [];
        $this->statuses_loaded = true;
    }

    /**
     * Re-runs registration, which also asks Stripe to verify a domain that
     * previously failed, then reads the outcome back rather than the cache.
     */
    public function recheckDomains(RegisterPaymentMethodDomains $register, FetchPaymentMethodDomainStatuses $statuses): void
    {
        $org = $this->organization();

        if (! $org || blank($org->stripe_account_id)) {
            return;
        }

        $register->register($org);

        // register() records any refusal on the organization, and the copy
        // hanging off the signed-in user still holds the settings from before.
        $org->refresh();
        Auth::user()?->setRelation('organization', $org);

        $this->domain_statuses = $statuses->fetch($org, fresh: true);
        $this->statuses_loaded = true;

        $this->dispatch('notify', message: 'Domain verification rechecked.', variant: 'success');
    }

    /**
     * The checkout runs in an iframe served from here, and Safari only allows
     * Apple Pay in a cross-origin iframe when the frame's own source domain is
     * registered too - not just the site embedding it. We register it for every
     * connected account, but until now there was nothing on screen saying
     * whether Stripe had accepted it.
     *
     * There are two of them: the frame every NGO site embeds, and the main site
     * that serves the donor portal and the hosted campaign pages.
     *
     * @return array<int, string>
     */
    public function checkoutDomains(): array
    {
        return CheckoutDomains::all();
    }

    /**
     * Verification can take a moment after Stripe first sees a domain, so an
     * unknown domain reads as pending rather than broken.
     *
     * @return array{label: string, tone: string, error: string|null}
     */
    public function walletStatusFor(string $domain): array
    {
        $normalized = DomainName::normalize($domain);
        $status = $this->domain_statuses[$normalized] ?? null;

        if ($status === null) {
            $refusal = $this->registrationErrorFor($normalized);

            // Stripe holds no record of it, so either registration was refused
            // - and we kept the reason - or the job simply has not run yet.
            return $refusal
                ? ['label' => 'Not registered', 'tone' => 'failed', 'error' => $refusal]
                : ['label' => 'Pending verification', 'tone' => 'pending', 'error' => null];
        }

        $active = collect(['apple_pay', 'google_pay'])
            ->filter(fn (string $wallet): bool => ($status[$wallet] ?? null) === 'active');

        return match ($active->count()) {
            2 => ['label' => 'Wallets active', 'tone' => 'active', 'error' => null],
            1 => ['label' => 'Partly active', 'tone' => 'partial', 'error' => $status['error']],
            default => ['label' => 'Not verified', 'tone' => 'failed', 'error' => $status['error']],
        };
    }

    private function registrationErrorFor(string $normalizedDomain): ?string
    {
        $errors = (array) ($this->organization()?->settings['payment_domain_errors'] ?? []);

        return $errors[$normalizedDomain] ?? null;
    }

    /**
     * Hosts donations actually arrived from that nobody has registered.
     *
     * A subdomain is a separate domain to Stripe, so a site embedded at
     * give.example.org loses its wallet buttons while example.org sits in the
     * list showing green - the failure is invisible from this page alone. The
     * donations already record the page they came from, so no extra tracking
     * is needed to spot it.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function unregisteredEmbeddingDomains(): array
    {
        $org = $this->organization();

        if (! $org) {
            return [];
        }

        $known = collect($this->normalizeDomains($this->allowed_domains))
            ->merge($this->checkoutDomains())
            ->filter()
            ->all();

        return Donation::query()
            ->whereIn('campaign_id', Campaign::query()->where('organization_id', $org->id)->select('id'))
            ->whereNotNull('page_url')
            ->where('created_at', '>=', now()->subDays(90))
            ->orderByDesc('id')
            ->limit(500)
            ->pluck('page_url')
            ->map(fn (string $url): string => DomainName::normalize((string) (parse_url($url, PHP_URL_HOST) ?: '')))
            ->filter()
            ->reject(fn (string $host): bool => in_array($host, $known, true))
            ->unique()
            ->values()
            ->all();
    }

    public function addDomain(string $domain): void
    {
        $domain = trim($domain);

        if ($domain === '') {
            return;
        }

        // Ihsan's own hosts are registered on every connected account and are
        // listed above the organiser's list, where they cannot be removed.
        // Adding one here would spend a slot on a domain we already manage and
        // put a removable copy of it on screen.
        if (CheckoutDomains::includes($domain)) {
            $this->dispatch('notify', message: DomainName::normalize($domain).' is an Ihsan domain and is always allowed.', variant: 'error');

            return;
        }

        if (count($this->allowed_domains) >= self::MAX_ALLOWED_DOMAINS) {
            $this->dispatch('notify', message: 'You can only add up to '.self::MAX_ALLOWED_DOMAINS.' allowed domains.', variant: 'error');

            return;
        }

        $this->allowed_domains[] = $domain;
    }

    public function removeDomain(int $index): void
    {
        array_splice($this->allowed_domains, $index, 1);
        $this->allowed_domains = array_values($this->allowed_domains);
    }

    public function save(): void
    {
        $this->validate([
            'allowed_domains' => ['nullable', 'array', 'max:'.self::MAX_ALLOWED_DOMAINS],
            'allowed_domains.*' => ['string', 'max:255'],
        ]);

        $org = $this->organization();

        if (! $org) {
            return;
        }

        $settings = array_merge($org->settings ?? [], [
            'allowed_domains' => $this->normalizeDomains($this->allowed_domains),
        ]);

        $org->update(['settings' => $settings]);

        if (filled($org->stripe_account_id)) {
            RegisterStripePaymentMethodDomains::dispatch($org->id);

            // The job registers in the background, so anything cached is stale
            // and a domain added just now reads as pending until it runs.
            $this->domain_statuses = app(FetchPaymentMethodDomainStatuses::class)->fetch($org, fresh: true);
            $this->statuses_loaded = true;
        }

        $this->dispatch('notify', message: 'Allowed domains saved.', variant: 'success');
    }

    private function organization(): ?Organization
    {
        /** @var Organization|null */
        return Auth::user()?->organization;
    }

    private function normalizeDomain(string $domain): string
    {
        return DomainName::normalize($domain);
    }

    /**
     * @param  array<int, string>  $domains
     * @return array<int, string>
     */
    private function normalizeDomains(array $domains): array
    {
        return collect($domains)
            ->filter()
            ->map(fn (string $domain): string => $this->normalizeDomain($domain))
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.app.settings.allow-domains');
    }
}
