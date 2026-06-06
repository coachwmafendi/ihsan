<?php

namespace App\Models;

use App\Services\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
