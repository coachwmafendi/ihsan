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

#[Fillable(['campaign_id', 'donor_id', 'subscription_id', 'stripe_payment_intent_id', 'stripe_charge_id', 'payment_method_brand', 'payment_method_type', 'donor_country', 'gross_amount', 'stripe_fee', 'donor_fee_covered', 'processing_fee', 'net_amount', 'currency', 'base_currency', 'base_amount', 'status', 'type', 'donor_message', 'is_anonymous', 'utm_params', 'invoice_number'])]
class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Donation $donation) {
            if ($donation->invoice_number === null) {
                $donation->invoice_number = 'INV-'.str_pad((string) $donation->id, 6, '0', STR_PAD_LEFT);
                $donation->saveQuietly();
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
            'donor_fee_covered' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'base_currency' => 'string',
            'base_amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'utm_params' => 'array',
            'status' => DonationStatus::class,
            'type' => DonationType::class,
        ];
    }
}
