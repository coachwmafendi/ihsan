<?php

namespace App\Models;

use App\Enums\ElementType;
use App\Services\PublicIdGenerator;
use Database\Factories\ElementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'campaign_id', 'public_id', 'name', 'token', 'type', 'config', 'is_active', 'is_donor_portal_default', 'form_slug'])]
class Element extends Model
{
    /** @use HasFactory<ElementFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Element $element) {
            if (! $element->public_id) {
                $element->public_id = PublicIdGenerator::generate(static::class);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config ?? [], $key, $default);
    }

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'is_donor_portal_default' => 'boolean',
            'type' => ElementType::class,
        ];
    }
}
