<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['campaign_id', 'donor_id', 'subscription_id', 'stripe_payment_intent_id', 'stripe_charge_id', 'payment_method_brand', 'payment_method_type', 'gross_amount', 'stripe_fee', 'processing_fee', 'net_amount', 'currency', 'status', 'type', 'donor_message', 'is_anonymous', 'utm_params'])]
class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function processingFee(): HasOne
    {
        return $this->hasOne(ProcessingFee::class);
    }

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'stripe_fee' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'utm_params' => 'array',
            'status' => DonationStatus::class,
            'type' => DonationType::class,
        ];
    }
}
