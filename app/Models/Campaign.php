<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\PaymentGateway;
use App\Services\PublicIdGenerator;
use Carbon\CarbonImmutable;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property string|null $description
 * @property string|null $image_path
 * @property numeric|null $target_amount
 * @property numeric $collected_amount
 * @property bool $has_target
 * @property bool $allow_recurring
 * @property CarbonImmutable|null $end_date
 * @property CampaignStatus $status
 * @property array<array-key, mixed>|null $suggested_amounts
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $headline
 * @property string|null $short_summary
 * @property numeric|null $minimum_amount
 * @property bool $allow_custom_amount
 * @property PaymentGateway|null $payment_gateway
 * @property string|null $thank_you_message
 * @property string|null $redirect_url
 * @property array<array-key, mixed>|null $suggested_amounts_one_time
 * @property array<array-key, mixed>|null $suggested_amounts_monthly
 * @property bool $impact_descriptions_enabled
 * @property numeric|null $default_monthly_amount
 * @property string|null $form_parameter
 * @property bool $checkout_modal_enabled
 * @property array<array-key, mixed>|null $checkout_allowed_domains
 * @property array<array-key, mixed>|null $milestones_notified
 * @property array<array-key, mixed>|null $config
 * @property bool $has_end_date
 * @property string|null $public_id
 * @property string|null $slug
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Collection<int, Donation> $donations
 * @property-read int|null $donations_count
 * @property-read Collection<int, Element> $elements
 * @property-read int|null $elements_count
 * @property-read Donation|null $latestDonation
 * @property-read Organization $organization
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 *
 * @method static \Database\Factories\CampaignFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereAllowCustomAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereAllowRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereCheckoutAllowedDomains($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereCheckoutModalEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereCollectedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereDefaultMonthlyAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereFormParameter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereHasEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereHasTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereHeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereImpactDescriptionsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereMilestonesNotified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereMinimumAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign wherePaymentGateway($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign wherePublicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereShortSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSuggestedAmounts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSuggestedAmountsMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSuggestedAmountsOneTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTargetAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereThankYouMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['organization_id', 'public_id', 'slug', 'title', 'description', 'headline', 'short_summary', 'image_path', 'target_amount', 'minimum_amount', 'allow_custom_amount', 'collected_amount', 'has_target', 'has_end_date', 'allow_recurring', 'payment_gateway', 'thank_you_message', 'redirect_url', 'end_date', 'status', 'suggested_amounts', 'suggested_amounts_one_time', 'suggested_amounts_monthly', 'impact_descriptions_enabled', 'default_monthly_amount', 'form_parameter',             'checkout_modal_enabled', 'campaign_page_enabled', 'checkout_allowed_domains', 'config'])]
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

            if (blank($campaign->slug) && ! blank($campaign->title)) {
                $base = Str::slug($campaign->title);
                $slug = $base;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$counter++;
                }

                $campaign->slug = $slug;
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

    /**
     * The currency this campaign's own figures are denominated in.
     *
     * target_amount, minimum_amount and collected_amount are all stored in it,
     * so anything quoting them has to say which currency it means rather than
     * assuming MYR.
     */
    public function defaultCurrency(): string
    {
        $currency = $this->config['default_currency']
            ?? $this->organization?->settings['default_currency']
            ?? 'MYR';

        return strtoupper((string) $currency);
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
        return $this->hasOne(Donation::class)
            ->ofMany(
                ['created_at' => 'max'],
                fn ($query) => $query->where('status', DonationStatus::Succeeded),
                'latest_donation'
            );
    }

    public function restore(): void
    {
        $this->update([
            'status' => CampaignStatus::Draft,
        ]);
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
            'campaign_page_enabled' => 'boolean',
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
