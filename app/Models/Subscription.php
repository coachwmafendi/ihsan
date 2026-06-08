<?php

namespace App\Models;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Services\PublicIdGenerator;
use App\Support\Currency;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['campaign_id', 'donor_id', 'source', 'public_id', 'stripe_subscription_id', 'stripe_price_id', 'amount', 'currency', 'interval', 'status', 'retry_count', 'payment_count', 'cancel_at_period_end', 'cover_fee', 'cancel_at', 'current_period_start', 'current_period_end', 'paused_until', 'cancelled_at'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription) {
            if (! $subscription->public_id) {
                $subscription->public_id = PublicIdGenerator::generate(static::class);
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function currencySymbol(): Attribute
    {
        return Attribute::get(fn () => Currency::symbol($this->currency));
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'retry_count' => 'integer',
            'payment_count' => 'integer',
            'cancel_at_period_end' => 'boolean',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'paused_until' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancel_at' => 'datetime',
            'cover_fee' => 'boolean',
            'interval' => SubscriptionInterval::class,
            'status' => SubscriptionStatus::class,
        ];
    }
}
