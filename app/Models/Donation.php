<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Support\Currency;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['campaign_id', 'donor_id', 'subscription_id', 'stripe_payment_intent_id', 'stripe_charge_id', 'payment_method_brand', 'payment_method_type', 'donor_country', 'gross_amount', 'stripe_fee', 'donor_fee_covered', 'processing_fee', 'stripe_fee_details', 'net_amount', 'currency', 'base_currency', 'base_amount', 'exchange_rate', 'status', 'type', 'donor_message', 'is_anonymous', 'utm_params', 'invoice_number', 'device_type', 'ip_address', 'browser', 'os', 'page_url', 'geo_city', 'geo_region', 'payment_method_last4', 'billing_address_line1', 'billing_address_line2', 'billing_address_city', 'billing_address_state', 'billing_address_postal_code', 'billing_country', 'receipt_sent_at', 'refunded_at', 'risk_score', 'risk_level', 'avs_result', 'cvc_result', 'fraud_status'])]
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

    public function currencySymbol(): Attribute
    {
        return Attribute::get(fn () => Currency::symbol($this->currency));
    }

    public function elementLabel(): Attribute
    {
        return Attribute::get(function () {
            $utm = $this->utm_params;
            $utm = is_string($utm) ? json_decode($utm, true) ?? [] : ($utm ?? []);

            if (! $utm || ($utm['source'] ?? null) !== 'element') {
                return null;
            }

            $type = ucwords(str_replace('_', ' ', $utm['element_type'] ?? ''));
            $name = $utm['element_name'] ?? '—';

            return "{$type} - {$name}";
        });
    }

    public function formattedAmount(): Attribute
    {
        return Attribute::get(fn () => $this->currency_symbol.' '.number_format((float) $this->gross_amount, 2));
    }

    public function paymentMethodDisplay(): Attribute
    {
        return Attribute::get(function () {
            $brand = $this->payment_method_brand;
            $last4 = $this->payment_method_last4;

            if (! $brand && ! $last4) {
                return 'Card';
            }

            $display = $brand ? ucfirst($brand) : 'Card';

            if ($last4) {
                $display .= ' **** '.$last4;
            }

            return $display;
        });
    }

    public function amountWithConversion(): Attribute
    {
        return Attribute::get(function () {
            $symbol = $this->currency_symbol;
            $amount = number_format((float) $this->gross_amount, 2);

            if ($this->currency !== 'myr' && $this->base_amount !== null) {
                $base = number_format((float) $this->base_amount, 2);

                if ($base !== $amount) {
                    return "≈ MYR {$base} ({$symbol} {$amount})";
                }
            }

            return "{$symbol} {$amount}";
        });
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
            'stripe_fee_details' => 'array',
            'status' => DonationStatus::class,
            'type' => DonationType::class,
            'risk_score' => 'integer',
            'refunded_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
        ];
    }
}
