<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Enums\TrackingProvider;
use App\Enums\TrackingProviderStatus;
use App\Models\TrackingConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Tracking extends Component
{
    /** @var array<string, array<string, string>> Credential values keyed by provider slug */
    public array $credentials = [];

    /** @var array<string, array<string, bool>> Option toggles keyed by provider slug */
    public array $options = [];

    // Advanced settings
    public string $attributionWindow = '30';

    public bool $captureUtmSource = true;

    public bool $captureUtmMedium = true;

    public bool $captureUtmCampaign = true;

    public bool $captureUtmContent = true;

    public bool $captureUtmTerm = true;

    public bool $captureFbclid = true;

    public bool $captureGclid = true;

    public bool $captureTtclid = true;

    public bool $captureReferrer = true;

    public bool $captureLandingPage = true;

    // UI state
    public bool $showAdvanced = false;

    public bool $showAttribution = false;

    public string $eventSearch = '';

    public string $eventFilter = '';

    public function mount(): void
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $configs = TrackingConfiguration::allForOrganization($org);

        foreach (TrackingProvider::cases() as $provider) {
            $config = $configs->first(fn (TrackingConfiguration $c) => $c->provider === $provider);

            $this->credentials[$provider->value] = array_merge(
                $provider->defaultCredentials(),
                $config?->credentials ?? []
            );

            $this->options[$provider->value] = array_merge(
                $provider->defaultOptions(),
                $config?->options ?? []
            );
        }

        $advanced = $org->settings['tracking_advanced'] ?? [];
        $this->attributionWindow = $advanced['attribution_window'] ?? '30';
        $this->captureUtmSource = (bool) ($advanced['capture_utm_source'] ?? true);
        $this->captureUtmMedium = (bool) ($advanced['capture_utm_medium'] ?? true);
        $this->captureUtmCampaign = (bool) ($advanced['capture_utm_campaign'] ?? true);
        $this->captureUtmContent = (bool) ($advanced['capture_utm_content'] ?? true);
        $this->captureUtmTerm = (bool) ($advanced['capture_utm_term'] ?? true);
        $this->captureFbclid = (bool) ($advanced['capture_fbclid'] ?? true);
        $this->captureGclid = (bool) ($advanced['capture_gclid'] ?? true);
        $this->captureTtclid = (bool) ($advanced['capture_ttclid'] ?? true);
        $this->captureReferrer = (bool) ($advanced['capture_referrer'] ?? true);
        $this->captureLandingPage = (bool) ($advanced['capture_landing_page'] ?? true);
    }

    public function saveProvider(string $providerSlug): void
    {
        $provider = TrackingProvider::from($providerSlug);
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $credentialKeys = array_column($provider->credentialFields(), 'key');
        $optionKeys = array_column($provider->optionFields(), 'key');

        $validationRules = [];
        foreach ($credentialKeys as $key) {
            $validationRules["credentials.{$providerSlug}.{$key}"] = ['nullable', 'string', 'max:1000'];
        }
        $this->validate($validationRules);

        $credentials = array_intersect_key(
            $this->credentials[$providerSlug] ?? [],
            array_flip($credentialKeys)
        );

        $options = array_intersect_key(
            $this->options[$providerSlug] ?? [],
            array_flip($optionKeys)
        );

        $primaryKey = $provider->primaryCredentialKey();
        $isConfigured = ! empty($credentials[$primaryKey]);

        $config = TrackingConfiguration::firstOrNew([
            'organization_id' => $org->id,
            'provider' => $providerSlug,
        ]);

        $config->credentials = $credentials;
        $config->options = $options;
        $config->is_enabled = $isConfigured;
        $config->status = $isConfigured
            ? TrackingProviderStatus::Active
            : TrackingProviderStatus::NotConfigured;
        $config->save();

        $this->dispatch('notify', type: 'success', message: "{$provider->label()} settings saved.");
    }

    public function testConnection(string $providerSlug): void
    {
        $provider = TrackingProvider::from($providerSlug);
        $primaryKey = $provider->primaryCredentialKey();

        if (empty($this->credentials[$providerSlug][$primaryKey] ?? '')) {
            $this->dispatch('notify', type: 'error', message: "Enter credentials for {$provider->label()} first.");

            return;
        }

        // TODO: dispatch a job to actually test the provider connection
        $this->dispatch('notify', type: 'success', message: "{$provider->label()} connection test sent.");
    }

    public function saveAdvanced(): void
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $settings = $org->settings ?? [];
        $settings['tracking_advanced'] = [
            'attribution_window' => $this->attributionWindow,
            'capture_utm_source' => $this->captureUtmSource,
            'capture_utm_medium' => $this->captureUtmMedium,
            'capture_utm_campaign' => $this->captureUtmCampaign,
            'capture_utm_content' => $this->captureUtmContent,
            'capture_utm_term' => $this->captureUtmTerm,
            'capture_fbclid' => $this->captureFbclid,
            'capture_gclid' => $this->captureGclid,
            'capture_ttclid' => $this->captureTtclid,
            'capture_referrer' => $this->captureReferrer,
            'capture_landing_page' => $this->captureLandingPage,
        ];

        $org->update(['settings' => $settings]);

        $this->dispatch('notify', type: 'success', message: 'Advanced tracking settings saved.');
    }

    /** @return array<int, TrackingConfiguration> */
    #[Computed]
    public function configurations(): array
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return [];
        }

        return TrackingConfiguration::allForOrganization($org)->all();
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function events(): array
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return [];
        }

        return $org->trackingEvents()
            ->when($this->eventSearch, fn ($q) => $q->where('event_name', 'like', "%{$this->eventSearch}%"))
            ->when($this->eventFilter, fn ($q) => $q->where('provider', $this->eventFilter))
            ->latest()
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function toggleShowAdvanced(): void
    {
        $this->showAdvanced = ! $this->showAdvanced;
    }

    public function toggleShowAttribution(): void
    {
        $this->showAttribution = ! $this->showAttribution;
    }

    public function render(): View
    {
        return view('livewire.app.settings.tracking', [
            'providers' => TrackingProvider::cases(),
        ]);
    }
}
