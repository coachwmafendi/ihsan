<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\PaymentGateway;
use App\Services\PublicIdGenerator;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['organization_id', 'public_id', 'title', 'description', 'headline', 'short_summary', 'image_path', 'target_amount', 'minimum_amount', 'allow_custom_amount', 'collected_amount', 'has_target', 'has_end_date', 'allow_recurring', 'payment_gateway', 'thank_you_message', 'redirect_url', 'end_date', 'status', 'suggested_amounts', 'suggested_amounts_one_time', 'suggested_amounts_monthly', 'impact_descriptions_enabled', 'default_monthly_amount', 'form_parameter', 'checkout_modal_enabled', 'checkout_allowed_domains', 'config'])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'target_amount', 'minimum_amount', 'has_target', 'has_end_date', 'allow_recurring', 'end_date', 'payment_gateway', 'thank_you_message', 'redirect_url', 'description', 'headline'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('campaign');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (! $campaign->public_id) {
                $campaign->public_id = PublicIdGenerator::generate(static::class);
            }
        });

        static::saving(function (Campaign $campaign) {
            if (blank($campaign->form_parameter)) {
                do {
                    $candidate = strtoupper(Str::random(5));
                } while (static::where('form_parameter', $candidate)->exists());

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

    public function latestDonation(): HasOne
    {
        return $this->hasOne(Donation::class)->ofMany();
    }

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'default_monthly_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'has_target' => 'boolean',
            'has_end_date' => 'boolean',
            'allow_recurring' => 'boolean',
            'allow_custom_amount' => 'boolean',
            'checkout_modal_enabled' => 'boolean',
            'impact_descriptions_enabled' => 'boolean',
            'end_date' => 'date',
            'suggested_amounts' => 'array',
            'suggested_amounts_one_time' => 'array',
            'suggested_amounts_monthly' => 'array',
            'config' => 'array',
            'checkout_allowed_domains' => 'array',
            'milestones_notified' => 'array',
            'status' => CampaignStatus::class,
            'payment_gateway' => PaymentGateway::class,
        ];
    }
}
