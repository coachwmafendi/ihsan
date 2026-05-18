<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'title', 'slug', 'description', 'image_path', 'target_amount', 'collected_amount', 'has_target', 'allow_recurring', 'end_date', 'status', 'suggested_amounts'])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function elements(): HasMany
    {
        return $this->hasMany(Element::class);
    }

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'has_target' => 'boolean',
            'allow_recurring' => 'boolean',
            'end_date' => 'date',
            'suggested_amounts' => 'array',
            'status' => CampaignStatus::class,
        ];
    }
}
