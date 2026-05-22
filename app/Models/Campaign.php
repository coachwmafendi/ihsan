<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\PaymentGateway;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['organization_id', 'title', 'slug', 'description', 'headline', 'short_summary', 'image_path', 'target_amount', 'minimum_amount', 'allow_custom_amount', 'collected_amount', 'has_target', 'allow_recurring', 'payment_gateway', 'thank_you_message', 'redirect_url', 'end_date', 'status', 'suggested_amounts', 'suggested_amounts_one_time', 'suggested_amounts_monthly', 'impact_descriptions_enabled', 'default_monthly_amount', 'form_parameter', 'checkout_modal_enabled', 'checkout_allowed_domains'])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Campaign $campaign) {
            if (blank($campaign->form_parameter) && filled($campaign->title)) {
                $base = Str::of($campaign->title)
                    ->upper()
                    ->replaceMatches('/[^A-Z0-9]/', '')
                    ->substr(0, 20);

                $candidate = $base;
                $i = 1;

                while (static::where('form_parameter', $candidate)
                    ->where('id', '!=', $campaign->id)
                    ->exists()
                ) {
                    $candidate = $base . $i;
                    $i++;
                }

                $campaign->form_parameter = $candidate;
            }
        });
    }

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
            'minimum_amount' => 'decimal:2',
            'default_monthly_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'has_target' => 'boolean',
            'allow_recurring' => 'boolean',
            'allow_custom_amount' => 'boolean',
            'checkout_modal_enabled' => 'boolean',
            'impact_descriptions_enabled' => 'boolean',
            'end_date' => 'date',
            'suggested_amounts' => 'array',
            'suggested_amounts_one_time' => 'array',
            'suggested_amounts_monthly' => 'array',
            'checkout_allowed_domains' => 'array',
            'status' => CampaignStatus::class,
            'payment_gateway' => PaymentGateway::class,
        ];
    }
}
