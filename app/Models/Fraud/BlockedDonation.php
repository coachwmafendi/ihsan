<?php

namespace App\Models\Fraud;

use App\Models\Donation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Fraud\BlockedDonationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $donation_id
 * @property string $reason
 * @property string $review_status
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $review_notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Donation $donation
 * @property-read User|null $reviewer
 *
 * @method static \Database\Factories\Fraud\BlockedDonationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereDonationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereReviewNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereReviewStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDonation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
