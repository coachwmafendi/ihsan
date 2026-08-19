<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DonorStripeCustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps a donor to the Stripe customer that represents them inside a single
 * organization's connected account.
 *
 * @property int $id
 * @property int $donor_id
 * @property int $organization_id
 * @property string $stripe_customer_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Donor $donor
 * @property-read Organization $organization
 *
 * @mixin \Eloquent
 */
class DonorStripeCustomer extends Model
{
    /** @use HasFactory<DonorStripeCustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'organization_id',
        'stripe_customer_id',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
