<?php

namespace App\Models;

use Database\Factories\PlatformFeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['donation_id', 'organization_id', 'fee_amount', 'fee_percentage', 'stripe_transfer_id', 'status', 'monthly_invoice_id'])]
class PlatformFee extends Model
{
    /** @use HasFactory<PlatformFeeFactory> */
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
