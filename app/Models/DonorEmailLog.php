<?php

namespace App\Models;

use App\Services\PublicIdGenerator;
use Carbon\CarbonImmutable;
use Database\Factories\DonorEmailLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $donor_id
 * @property int|null $organization_id
 * @property int|null $donation_id
 * @property int|null $subscription_id
 * @property int|null $resent_from_id
 * @property string $mailable_class
 * @property string $subject
 * @property array<array-key, mixed>|null $metadata
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $opened_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $public_id
 * @property-read Donor $donor
 * @property-read Organization|null $organization
 * @property-read Donation|null $donation
 * @property-read Subscription|null $subscription
 * @property-read DonorEmailLog|null $originalLog
 * @property-read Collection<int, DonorEmailLog> $resends
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorEmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorEmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorEmailLog query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'donor_id', 'organization_id', 'donation_id', 'subscription_id', 'resent_from_id',
    'mailable_class', 'message_id', 'provider_message_id', 'subject', 'delivery_status',
    'metadata', 'sent_at', 'opened_at', 'delivered_at', 'bounced_at', 'bounce_reason', 'complained_at',
])]
class DonorEmailLog extends Model
{
    /** @use HasFactory<DonorEmailLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'delivered_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DonorEmailLog $log) {
            if (! $log->public_id) {
                $log->public_id = PublicIdGenerator::generate(static::class);
            }
        });
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function originalLog(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }

    public function resends(): HasMany
    {
        return $this->hasMany(self::class, 'resent_from_id');
    }
}
