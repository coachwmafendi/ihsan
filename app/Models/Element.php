<?php

namespace App\Models;

use App\Enums\ElementType;
use App\Services\PublicIdGenerator;
use Carbon\CarbonImmutable;
use Database\Factories\ElementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $campaign_id
 * @property string $name
 * @property string $token
 * @property ElementType $type
 * @property array<array-key, mixed>|null $config
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $form_slug
 * @property bool $is_donor_portal_default
 * @property string|null $public_id
 * @property CarbonImmutable|null $archived_at
 * @property-read Campaign|null $campaign
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Organization $organization
 *
 * @method static \Database\Factories\ElementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereFormSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereIsDonorPortalDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element wherePublicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Element whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['organization_id', 'campaign_id', 'public_id', 'name', 'token', 'type', 'config', 'is_active', 'is_donor_portal_default', 'form_slug', 'archived_at'])]
class Element extends Model
{
    /** @use HasFactory<ElementFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'is_active', 'is_donor_portal_default', 'config', 'archived_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('element');
    }

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

    public function archive(): void
    {
        $this->update([
            'archived_at' => now(),
            'is_active' => false,
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'archived_at' => null,
        ]);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'is_donor_portal_default' => 'boolean',
            'type' => ElementType::class,
            'archived_at' => 'immutable_datetime',
        ];
    }
}
