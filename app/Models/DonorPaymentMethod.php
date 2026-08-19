<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DonorPaymentMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $donor_id
 * @property string $stripe_payment_method_id
 * @property string $brand
 * @property string $last4
 * @property int|null $exp_month
 * @property int|null $exp_year
 * @property string|null $country
 * @property bool $is_default
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Donor $donor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereDonorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereExpMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereExpYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereLast4($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereStripePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorPaymentMethod whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class DonorPaymentMethod extends Model
{
    /** @use HasFactory<DonorPaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'stripe_payment_method_id',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'country',
        'is_default',
    ];

    protected $casts = [
        'exp_month' => 'integer',
        'exp_year' => 'integer',
        'is_default' => 'boolean',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    /**
     * The last moment this card can be charged.
     *
     * Cards stay valid through the whole of their expiry month.
     */
    public function expiresAt(): ?CarbonImmutable
    {
        if ($this->exp_month === null || $this->exp_year === null) {
            return null;
        }

        return CarbonImmutable::create($this->exp_year, $this->exp_month, 1)->endOfMonth();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt()?->isPast() ?? false;
    }

    /**
     * Expires soon enough that the next monthly charge could fall after it.
     */
    public function expiresSoon(int $withinMonths = 2): bool
    {
        $expiresAt = $this->expiresAt();

        if ($expiresAt === null || $expiresAt->isPast()) {
            return false;
        }

        // Compare whole months: a card expiring late in a month is still due to
        // lapse in that month, whatever day of the month it is today.
        return $expiresAt->lessThanOrEqualTo(CarbonImmutable::now()->addMonths($withinMonths)->endOfMonth());
    }
}
