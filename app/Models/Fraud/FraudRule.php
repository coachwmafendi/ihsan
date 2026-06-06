<?php

namespace App\Models\Fraud;

use App\Models\Organization;
use Database\Factories\Fraud\FraudRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
