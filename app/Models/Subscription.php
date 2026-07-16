<?php

namespace App\Models;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Services\PublicIdGenerator;
use App\Support\Currency;
use Carbon\CarbonImmutable;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $donor_id
 * @property int|null $donor_payment_method_id
 * @property string|null $stripe_subscription_id
 * @property string|null $chip_recurring_token
 * @property string|null $stripe_price_id
 * @property numeric $amount
 * @property string $currency
 * @property SubscriptionInterval $interval
 * @property SubscriptionStatus $status
 * @property int $retry_count
 * @property int $payment_count
 * @property int $failed_installment_count
 * @property CarbonImmutable|null $current_period_start
 * @property CarbonImmutable|null $current_period_end
 * @property CarbonImmutable|null $next_charge_at
 * @property CarbonImmutable|null $last_charge_at
 * @property CarbonImmutable|null $last_charge_attempt_at
 * @property CarbonImmutable|null $paused_until
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property bool $cancel_at_period_end
 * @property bool $cover_fee
 * @property numeric|null $fee_cover_amount
 * @property CarbonImmutable|null $cancel_at
 * @property numeric|null $max_plan_amount
 * @property int|null $max_plan_installments
 * @property string|null $public_id
 * @property string|null $source
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Campaign $campaign
 * @property-read mixed $currency_symbol
 * @property-read Collection<int, Donation> $donations
 * @property-read int|null $donations_count
 * @property-read Donor $donor
 * @property-read DonorPaymentMethod|null $donorPaymentMethod
 *
 * @method static \Database\Factories\SubscriptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCancelAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCancelAtPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereChipRecurringToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCoverFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCurrentPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCurrentPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereDonorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePausedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePaymentCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePublicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereRetryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStripePriceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStripeSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['campaign_id', 'donor_id', 'donor_payment_method_id', 'source', 'public_id', 'stripe_subscription_id', 'chip_recurring_token', 'stripe_price_id', 'amount', 'currency', 'interval', 'status', 'retry_count', 'payment_count', 'failed_installment_count', 'cancel_at_period_end', 'cover_fee', 'fee_cover_amount', 'cancel_at', 'max_plan_amount', 'max_plan_installments', 'current_period_start', 'current_period_end', 'next_charge_at', 'last_charge_at', 'last_charge_attempt_at', 'paused_until', 'cancelled_at', 'cancellation_reason'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'cancel_at_period_end', 'cancelled_at', 'paused_until', 'cancel_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('subscription');
    }

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

    public function donorPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(DonorPaymentMethod::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(DonorEmailLog::class)->latest('sent_at');
    }

    public function currencySymbol(): Attribute
    {
        return Attribute::get(fn () => Currency::symbol($this->currency));
    }

    /**
     * Canonical money format shared with the donation detail page:
     * MYR amounts as "MYR 100.00", foreign amounts as "$ 50.00 USD".
     */
    public function displayAmount(?float $amount = null): string
    {
        $amount ??= (float) $this->amount;

        if (strtolower((string) $this->currency) === 'myr') {
            return 'MYR '.number_format($amount, 2);
        }

        return $this->currency_symbol.' '.number_format($amount, 2).' '.strtoupper((string) $this->currency);
    }

    public function sourceLabel(): Attribute
    {
        return Attribute::get(function (): string {
            return match ($this->source) {
                'element' => 'Element',
                'campaign_page' => 'Campaign Page',
                'checkout_modal' => 'Checkout Modal',
                'virtual_terminal' => 'Virtual Terminal',
                default => 'Checkout Modal',
            };
        });
    }

    public function isScheduledToCancel(): Attribute
    {
        return Attribute::get(fn () => $this->cancel_at_period_end);
    }

    public function statusLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->status === SubscriptionStatus::Active && $this->cancel_at_period_end) {
                return 'Scheduled to cancel';
            }

            return $this->status->getLabel();
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'retry_count' => 'integer',
            'payment_count' => 'integer',
            'failed_installment_count' => 'integer',
            'donor_payment_method_id' => 'integer',
            'cancel_at_period_end' => 'boolean',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'next_charge_at' => 'datetime',
            'last_charge_at' => 'datetime',
            'last_charge_attempt_at' => 'datetime',
            'paused_until' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancel_at' => 'datetime',
            'cover_fee' => 'boolean',
            'max_plan_amount' => 'decimal:2',
            'max_plan_installments' => 'integer',
            'interval' => SubscriptionInterval::class,
            'status' => SubscriptionStatus::class,
        ];
    }
}
