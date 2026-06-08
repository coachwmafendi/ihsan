<?php

namespace App\Models;

use Database\Factories\DonorPaymentMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
