<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AllowDomains extends Component
{
    public const MAX_ALLOWED_DOMAINS = 10;

    public array $allowed_domains = [];

    public function mount(): void
    {
        /** @var Organization|null $org */
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $settings = $org->settings ?? [];
        $this->allowed_domains = $settings['allowed_domains'] ?? [];

        if ($this->allowed_domains === [] && $org->website_url) {
            $domain = $this->normalizeDomain($org->website_url);

            if ($domain !== '') {
                $this->allowed_domains[] = $domain;
            }
        }
    }

    public function addDomain(string $domain): void
    {
        $domain = trim($domain);

        if ($domain === '') {
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

        /** @var Organization|null $org */
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $settings = array_merge($org->settings ?? [], [
            'allowed_domains' => $this->normalizeDomains($this->allowed_domains),
        ]);

        $org->update(['settings' => $settings]);

        $this->dispatch('notify', message: 'Allowed domains saved.', variant: 'success');
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
        return view('livewire.app.settings.allow-domains', ['title' => 'Settings — Allowed Domains']);
    }
}
