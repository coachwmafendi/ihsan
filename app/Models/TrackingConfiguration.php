<?php

namespace App\Models;

use App\Enums\TrackingProvider;
use App\Enums\TrackingProviderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'provider', 'is_enabled', 'credentials', 'options', 'status', 'error_message', 'last_tested_at', 'last_event_at'])]
class TrackingConfiguration extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'provider' => TrackingProvider::class,
            'status' => TrackingProviderStatus::class,
            'is_enabled' => 'boolean',
            'credentials' => 'encrypted:array',
            'options' => 'array',
            'last_tested_at' => 'immutable_datetime',
            'last_event_at' => 'immutable_datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TrackingEvent::class, 'provider', 'provider')
            ->where('organization_id', $this->organization_id);
    }

    /**
     * Load all provider configs for an org, creating missing rows on the fly.
     *
     * @return Collection<int, TrackingConfiguration>
     */
    public static function allForOrganization(Organization $organization): Collection
    {
        $existing = static::where('organization_id', $organization->id)
            ->get()
            ->keyBy(fn (self $c) => $c->provider->value);

        $configs = new Collection;

        foreach (TrackingProvider::cases() as $provider) {
            if ($existing->has($provider->value)) {
                $configs->push($existing->get($provider->value));
            } else {
                $config = new self;
                $config->organization_id = $organization->id;
                $config->provider = $provider;
                $config->is_enabled = false;
                $config->status = TrackingProviderStatus::NotConfigured;
                $config->credentials = $provider->defaultCredentials();
                $config->options = $provider->defaultOptions();
                $configs->push($config);
            }
        }

        return $configs;
    }

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured($this);
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return ($this->credentials ?? [])[$key] ?? $default;
    }

    public function option(string $key, bool $default = false): bool
    {
        return (bool) (($this->options ?? [])[$key] ?? $default);
    }
}
