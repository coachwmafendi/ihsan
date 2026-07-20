<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'stripe_payout_id',
        'amount',
        'currency',
        'status',
        'arrival_date',
        'paid_at',
        'bank_name',
        'bank_account_last4',
        'failure_code',
        'failure_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'arrival_date' => 'date',
            'paid_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->id);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
