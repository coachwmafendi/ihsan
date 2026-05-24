<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'stripe_invoice_id',
        'invoice_number',
        'period',
        'total_fees',
        'stripe_status',
        'paid_at',
        'stripe_invoice_url',
        'stripe_invoice_pdf',
    ];

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
