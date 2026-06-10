<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProcessingFeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $donation_id
 * @property int $organization_id
 * @property numeric $fee_amount
 * @property numeric $fee_percentage
 * @property string|null $stripe_transfer_id
 * @property string $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property int|null $monthly_invoice_id
 * @property-read Donation $donation
 * @property-read MonthlyInvoice|null $monthlyInvoice
 * @property-read Organization $organization
 *
 * @method static \Database\Factories\ProcessingFeeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereDonationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereFeeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereFeePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereMonthlyInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereStripeTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessingFee whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['donation_id', 'organization_id', 'fee_amount', 'fee_percentage', 'stripe_transfer_id', 'status', 'monthly_invoice_id'])]
class ProcessingFee extends Model
{
    /** @use HasFactory<ProcessingFeeFactory> */
    use HasFactory;

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function monthlyInvoice(): BelongsTo
    {
        return $this->belongsTo(MonthlyInvoice::class);
    }

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:2',
            'fee_percentage' => 'decimal:2',
        ];
    }
}
