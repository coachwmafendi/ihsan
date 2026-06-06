<?php

namespace App\Models\Fraud;

use App\Models\Donation;
use App\Models\User;
use Database\Factories\Fraud\BlockedDonationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedDonation extends Model
{
    /** @use HasFactory<BlockedDonationFactory> */
    use HasFactory;

    protected $fillable = [
        'donation_id',
        'reason',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
