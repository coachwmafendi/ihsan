<?php

namespace App\Models\Fraud;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Database\Factories\Fraud\FraudRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $rule_type
 * @property array<array-key, mixed> $config
 * @property string $action
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organization $organization
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereRuleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FraudRule whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class FraudRule extends Model
{
    /** @use HasFactory<FraudRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'rule_type',
        'config',
        'action',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
