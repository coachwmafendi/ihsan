<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Services\PublicIdGenerator;
use App\Support\Currency;
use Carbon\CarbonImmutable;
use Database\Factories\DonationFactory;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $donor_id
 * @property int|null $subscription_id
 * @property string|null $stripe_payment_intent_id
 * @property string|null $chip_purchase_id
 * @property string|null $chip_recurring_token
 * @property string|null $chip_checkout_url
 * @property string|null $stripe_charge_id
 * @property string|null $stripe_invoice_id
 * @property numeric $gross_amount
 * @property numeric $stripe_fee
 * @property numeric $processing_fee
 * @property numeric $net_amount
 * @property string $currency
 * @property DonationStatus $status
 * @property DonationType $type
 * @property string|null $donor_message
 * @property bool $is_anonymous
 * @property array<array-key, mixed>|null $utm_params
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $finalized_at
 * @property string|null $payment_method_brand
 * @property string|null $payment_method_type
 * @property string|null $donor_country
 * @property string|null $invoice_number
 * @property string|null $base_currency
 * @property numeric|null $base_amount
 * @property numeric $donor_fee_covered
 * @property string|null $device_type
 * @property string|null $ip_address
 * @property string|null $browser
 * @property string|null $os
 * @property string|null $page_url
 * @property string|null $geo_city
 * @property string|null $geo_region
 * @property numeric|null $exchange_rate
 * @property CarbonImmutable|null $receipt_sent_at
 * @property CarbonImmutable|null $new_donation_notification_sent_at
 * @property CarbonImmutable|null $large_donation_notification_sent_at
 * @property string|null $payment_method_last4
 * @property int|null $payment_method_exp_month
 * @property int|null $payment_method_exp_year
 * @property string|null $billing_address_line1
 * @property string|null $billing_address_line2
 * @property string|null $billing_address_city
 * @property string|null $billing_address_state
 * @property string|null $billing_address_postal_code
 * @property string|null $billing_country
 * @property CarbonImmutable|null $refunded_at
 * @property array<array-key, mixed>|null $stripe_fee_details
 * @property int|null $risk_score
 * @property string|null $risk_level
 * @property string|null $avs_result
 * @property string|null $cvc_result
 * @property string $fraud_status
 * @property string|null $public_id
 * @property string|null $receipt_token
 * @property string|null $source
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read mixed $amount_with_conversion
 * @property-read Campaign $campaign
 * @property-read mixed $currency_symbol
 * @property-read Donor $donor
 * @property-read mixed $element_label
 * @property-read mixed $formatted_amount
 * @property-read float $total_charged
 * @property-read mixed $total_charged_with_conversion
 * @property-read mixed $payment_method_display
 * @property-read ProcessingFee|null $processingFee
 * @property-read Subscription|null $subscription
 *
 * @method static \Database\Factories\DonationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereAvsResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBaseAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBaseCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBillingAddressCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBillingAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBillingAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBillingAddressPostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBillingAddressState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBillingCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereBrowser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereChipCheckoutUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereChipPurchaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereChipRecurringToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereCvcResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereDonorCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereDonorFeeCovered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereDonorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereDonorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereFraudStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereGeoCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereGeoRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereGrossAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereIsAnonymous($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereNetAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation wherePageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation wherePaymentMethodBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation wherePaymentMethodLast4($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation wherePaymentMethodType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereProcessingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation wherePublicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereReceiptSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereRefundedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereRiskLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereRiskScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereStripeChargeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereStripeFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereStripeFeeDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereStripePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donation whereUtmParams($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['campaign_id', 'donor_id', 'subscription_id', 'source', 'public_id', 'receipt_token', 'stripe_payment_intent_id', 'chip_purchase_id', 'chip_recurring_token', 'chip_checkout_url', 'stripe_charge_id', 'stripe_invoice_id', 'payment_method_brand', 'payment_method_type', 'donor_country', 'gross_amount', 'stripe_fee', 'chip_fee', 'donor_fee_covered', 'processing_fee', 'stripe_fee_details', 'net_amount', 'currency', 'base_currency', 'base_amount', 'exchange_rate', 'status', 'type', 'donor_message', 'is_anonymous', 'utm_params', 'invoice_number', 'device_type', 'ip_address', 'browser', 'os', 'page_url', 'geo_city', 'geo_region', 'payment_method_last4', 'payment_method_exp_month', 'payment_method_exp_year', 'billing_address_line1', 'billing_address_line2', 'billing_address_city', 'billing_address_state', 'billing_address_postal_code', 'billing_country', 'receipt_sent_at', 'new_donation_notification_sent_at', 'large_donation_notification_sent_at', 'refunded_at', 'finalized_at', 'risk_score', 'risk_level', 'avs_result', 'cvc_result', 'fraud_status'])]
class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'refunded_at', 'fraud_status', 'risk_score', 'risk_level'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('donation');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (Donation $donation) {
            if (! $donation->public_id) {
                $donation->public_id = PublicIdGenerator::generate(static::class);
            }

            if (! $donation->receipt_token) {
                $donation->receipt_token = static::generateReceiptToken();
            }
        });

        static::created(function (Donation $donation) {
            if ($donation->invoice_number === null) {
                $donation->invoice_number = 'INV-'.str_pad((string) $donation->id, 6, '0', STR_PAD_LEFT);
                $donation->saveQuietly();
            }
        });
    }

    public static function generateReceiptToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::query()->where('receipt_token', $token)->exists());

        return $token;
    }

    public function receiptUrl(): string
    {
        return route('receipts.token', ['donation' => $this, 'token' => $this->receipt_token]);
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

    public function emailLogs(): HasMany
    {
        return $this->hasMany(DonorEmailLog::class);
    }

    public function currencySymbol(): Attribute
    {
        return Attribute::get(fn () => Currency::symbol($this->currency));
    }

    public function element(): Attribute
    {
        return Attribute::get(function (): ?Element {
            $utm = $this->utm_params;
            $utm = is_string($utm) ? json_decode($utm, true) ?? [] : ($utm ?? []);

            $token = $utm['element_token'] ?? null;

            if (! $token) {
                return null;
            }

            return Element::where('token', $token)->first();
        });
    }

    public function sourceLabel(): Attribute
    {
        return Attribute::get(function (): string {
            return match ($this->source) {
                'campaign_page' => 'Campaign Page',
                'virtual_terminal' => 'Virtual Terminal',
                // 'element' and legacy null sources surface as Checkout Modal.
                default => 'Checkout Modal',
            };
        });
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
        return Attribute::get(fn () => Currency::format((string) $this->currency, (float) $this->gross_amount));
    }

    /**
     * Canonical money format shared with the donation detail page:
     * always "{CODE} {amount}" (e.g. "MYR 100.00", "SGD 50.00", "USD 50.00").
     */
    public function displayAmount(float $amount, bool $asBaseCurrency = false): string
    {
        $currency = $asBaseCurrency || strtolower((string) $this->currency) === 'myr'
            ? 'MYR'
            : strtoupper((string) $this->currency);

        return Currency::format($currency, $amount);
    }

    /** Donor fee cover converted to MYR using the settled exchange rate. */
    public function feeCoveredInBaseCurrency(): float
    {
        $exchangeRate = (float) ($this->exchange_rate ?? 0);

        return $exchangeRate > 0
            ? round((float) $this->donor_fee_covered * $exchangeRate, 2)
            : (float) $this->donor_fee_covered;
    }

    /** Donation amount (gross) with the MYR conversion appended when known. */
    public function displayDonationAmount(): Attribute
    {
        return Attribute::get(function () {
            $formatted = $this->displayAmount((float) $this->gross_amount);

            if (strtolower((string) $this->currency) !== 'myr' && $this->base_amount !== null) {
                return $formatted.' (≈ MYR '.number_format((float) $this->base_amount, 2).')';
            }

            return $formatted;
        });
    }

    /** Total charged to the donor with the MYR conversion appended when known. */
    public function displayPaymentAmount(): Attribute
    {
        return Attribute::get(function () {
            $formatted = $this->displayAmount($this->total_charged);

            if (strtolower((string) $this->currency) !== 'myr' && $this->base_amount !== null) {
                $base = (float) $this->base_amount + $this->feeCoveredInBaseCurrency();

                return $formatted.' (≈ MYR '.number_format($base, 2).')';
            }

            return $formatted;
        });
    }

    public function displayFeeCovered(): Attribute
    {
        return Attribute::get(fn () => $this->displayAmount((float) $this->donor_fee_covered));
    }

    /**
     * net_amount is only in MYR once the settled exchange rate has been
     * synced; before that it is still in the donation's charge currency.
     * Mirrors the payout amount shown on the donation detail page.
     */
    public function formattedNetAmount(): Attribute
    {
        return Attribute::get(function () {
            $isMyr = strtolower((string) $this->currency) === 'myr' || $this->base_amount !== null;

            return $this->displayAmount((float) $this->net_amount, $isMyr);
        });
    }

    public function paymentMethodDisplay(): Attribute
    {
        return Attribute::get(function () {
            $brand = $this->payment_method_brand;
            $last4 = $this->payment_method_last4;

            if (! $brand && ! $last4) {
                return 'Card';
            }

            $display = $brand ? Str::headline($brand) : 'Card';

            if ($last4) {
                $display .= ' **** '.$last4;
            }

            return $display;
        });
    }

    public function cardIconComponent(): Attribute
    {
        return Attribute::get(function (): string {
            $pmType = strtolower($this->payment_method_type ?? '');

            if ($pmType === 'apple_pay') {
                return 'icons.apple-pay';
            }

            if ($pmType === 'google_pay') {
                return 'icons.google-pay';
            }

            return match (strtolower($this->payment_method_brand ?? '')) {
                'visa' => 'icons.visa',
                'mastercard' => 'icons.mastercard',
                'amex' => 'icons.amex',
                'discover' => 'icons.discover',
                'diners' => 'icons.diners',
                'jcb' => 'icons.jcb',
                'maestro' => 'icons.maestro',
                'unionpay' => 'icons.unionpay',
                default => 'icons.credit-card',
            };
        });
    }

    public function installmentNumber(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->subscription_id) {
                return null;
            }

            return static::where('subscription_id', $this->subscription_id)
                ->where('created_at', '<=', $this->created_at)
                ->count();
        });
    }

    public function installmentNumberLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            $number = $this->installment_number;

            if ($number === null) {
                return null;
            }

            $ordinal = $number.((($number % 100) >= 11 && ($number % 100) <= 13) ? 'th' : match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            });

            return $ordinal.' installment';
        });
    }

    public function amountWithConversion(): Attribute
    {
        return Attribute::get(fn () => $this->display_donation_amount);
    }

    public function reportAmount(): Attribute
    {
        return Attribute::get(function () {
            if ($this->base_amount !== null) {
                return (float) $this->base_amount;
            }

            $gross = (float) $this->gross_amount;

            if (strtolower($this->currency ?? '') === 'myr') {
                return $gross;
            }

            if ($this->exchange_rate !== null) {
                return $gross * (float) $this->exchange_rate;
            }

            return $gross;
        });
    }

    public function isReportApproximate(): Attribute
    {
        return Attribute::get(fn () => $this->currency !== 'myr');
    }

    public function formattedReportAmount(): Attribute
    {
        return Attribute::get(function () {
            if ($this->currency !== 'myr' && $this->base_amount === null && $this->exchange_rate === null) {
                return $this->formatted_amount;
            }

            $prefix = $this->is_report_approximate ? '≈ MYR' : 'MYR';

            return $prefix.' '.number_format($this->report_amount, 2);
        });
    }

    public function formattedOriginalAmount(): Attribute
    {
        return Attribute::get(fn () => $this->formatted_amount);
    }

    public function totalCharged(): Attribute
    {
        return Attribute::get(fn () => (float) $this->gross_amount + (float) $this->donor_fee_covered);
    }

    public function totalChargedWithConversion(): Attribute
    {
        return Attribute::get(fn () => $this->display_payment_amount);
    }

    public static function reportAmountColumn(): Expression
    {
        $table = (new static)->getTable();
        $baseAmount = $table.'.base_amount';
        $grossAmount = $table.'.gross_amount';
        $currency = $table.'.currency';
        $exchangeRate = $table.'.exchange_rate';

        return DB::raw("COALESCE({$baseAmount}, CASE WHEN LOWER({$currency}) = 'myr' THEN {$grossAmount} ELSE {$grossAmount} * {$exchangeRate} END, {$grossAmount})");
    }

    public static function reportAmountSql(): string
    {
        return DB::connection()->getQueryGrammar()->getValue(self::reportAmountColumn());
    }

    public static function reportSumColumn(): Expression
    {
        $table = (new static)->getTable();
        $baseAmount = $table.'.base_amount';
        $grossAmount = $table.'.gross_amount';
        $currency = $table.'.currency';
        $exchangeRate = $table.'.exchange_rate';

        return DB::raw("SUM(COALESCE({$baseAmount}, CASE WHEN LOWER({$currency}) = 'myr' THEN {$grossAmount} ELSE {$grossAmount} * {$exchangeRate} END, {$grossAmount}))");
    }

    public static function hasReportApproximations(Builder $query): bool
    {
        return (clone $query)
            ->where('currency', '!=', 'myr')
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'stripe_fee' => 'decimal:2',
            'chip_fee' => 'decimal:2',
            'donor_fee_covered' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'base_currency' => 'string',
            'base_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'is_anonymous' => 'boolean',
            'utm_params' => 'array',
            'stripe_fee_details' => 'array',
            'status' => DonationStatus::class,
            'type' => DonationType::class,
            'risk_score' => 'integer',
            'refunded_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
            'new_donation_notification_sent_at' => 'datetime',
            'large_donation_notification_sent_at' => 'datetime',
        ];
    }
}
