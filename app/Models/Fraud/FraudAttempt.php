<?php

namespace App\Models\Fraud;

use App\Models\Donor;
use Carbon\CarbonImmutable;
use Database\Factories\Fraud\FraudAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $donor_id
 * @property string|null $email
 * @property string|null $ip_address
 * @property string|null $card_fingerprint
 * @property numeric|null $amount
 * @property string|null $currency
 * @property string $reason
 * @property string $action
 * @property array<array-key, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Donor|null $donor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereCardFingerprint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereDonorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudAttempt whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
