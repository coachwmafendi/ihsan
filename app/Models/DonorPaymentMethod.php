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
}
