<?php

namespace App\Models;

use App\Enums\TrackingProvider;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'donation_id', 'provider', 'event_name', 'status', 'amount', 'currency', 'campaign_name', 'payload', 'response'])]
class TrackingEvent extends Model
{
    protected function casts(): array
    {
        return [
            'provider' => TrackingProvider::class,
            'amount' => 'decimal:2',
            'payload' => 'array',
            'response' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
