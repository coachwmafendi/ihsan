<?php

namespace App\Models\Fraud;

use App\Models\Donor;
use Database\Factories\Fraud\FraudAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudAttempt extends Model
{
    /** @use HasFactory<FraudAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'email',
        'ip_address',
        'card_fingerprint',
        'amount',
        'currency',
        'reason',
        'action',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }
}
