<?php

namespace App\Models;

use App\Services\PublicIdGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $stripe_invoice_id
 * @property string $invoice_number
 * @property CarbonImmutable $period
 * @property numeric $total_fees
 * @property string $stripe_status
 * @property CarbonImmutable|null $paid_at
 * @property string|null $stripe_invoice_url
 * @property string|null $stripe_invoice_pdf
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $public_id
 * @property-read Organization $organization
 * @property-read Collection<int, ProcessingFee> $processingFees
 * @property-read int|null $processing_fees_count
 *
 * @method static \Database\Factories\MonthlyInvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice wherePublicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereStripeInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereStripeInvoicePdf($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereStripeInvoiceUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereStripeStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereTotalFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyInvoice whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class MonthlyInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'public_id',
        'stripe_invoice_id',
        'invoice_number',
        'period',
        'total_fees',
        'stripe_status',
        'paid_at',
        'stripe_invoice_url',
        'stripe_invoice_pdf',
    ];

    protected static function booted(): void
    {
        static::creating(function (MonthlyInvoice $invoice) {
            if (! $invoice->public_id) {
                $invoice->public_id = PublicIdGenerator::generate(static::class);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function processingFees(): HasMany
    {
        return $this->hasMany(ProcessingFee::class);
    }

    protected function casts(): array
    {
        return [
            'period' => 'date:Y-m-d',
            'total_fees' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
