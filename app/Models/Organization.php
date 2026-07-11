<?php

namespace App\Models;

use App\Actions\Chip\PaymentMethodWhitelistMapper;
use App\Enums\OrganizationStatus;
use App\Services\PublicIdGenerator;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string|null $public_id
 * @property string $name
 * @property string|null $ros_rob_number
 * @property string $registration_type
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $website_url
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property OrganizationStatus $status
 * @property string|null $stripe_account_id
 * @property string|null $chip_brand_id
 * @property string|null $chip_api_key
 * @property string|null $chip_webhook_id
 * @property string|null $chip_webhook_public_key
 * @property bool $stripe_onboarded
 * @property bool $stripe_enabled
 * @property bool $chip_onboarded
 * @property bool $chip_enabled
 * @property bool $chip_active
 * @property bool $stripe_active
 * @property string|null $bank_account_name
 * @property string|null $bank_account_number
 * @property string|null $bank_name
 * @property array<array-key, mixed>|null $settings
 * @property CarbonImmutable|null $approved_at
 * @property int|null $approved_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string $code
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postcode
 * @property string|null $country
 * @property string|null $sector
 * @property bool $tax_exempt
 * @property CarbonImmutable|null $stripe_onboarded_at
 * @property numeric|null $processing_fee_override
 * @property string|null $admin_notes
 * @property string $fee_collection_method
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Collection<int, Campaign> $campaigns
 * @property-read int|null $campaigns_count
 * @property-read Collection<int, OrganizationDocument> $documents
 * @property-read int|null $documents_count
 * @property-read Collection<int, Element> $elements
 * @property-read int|null $elements_count
 * @property-read Collection<int, ProcessingFee> $processingFees
 * @property-read int|null $processing_fees_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\OrganizationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereBankAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereChipApiKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereChipBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereFeeCollectionMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereProcessingFeeOverride($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereRegistrationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereRosRobNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereSector($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereStripeAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereStripeOnboarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereStripeOnboardedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereTaxExempt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereWebsiteUrl($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['public_id', 'name', 'code', 'ros_rob_number', 'registration_type', 'description', 'logo_path', 'website_url', 'facebook_url', 'contact_email', 'contact_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postcode', 'country', 'sector', 'tax_exempt', 'processing_fee_override', 'admin_notes', 'status', 'stripe_account_id', 'chip_brand_id', 'chip_api_key', 'chip_webhook_id', 'chip_webhook_public_key', 'stripe_onboarded', 'stripe_onboarded_at', 'stripe_enabled', 'chip_enabled', 'bank_account_name', 'bank_account_number', 'bank_name', 'settings', 'fee_collection_method', 'approved_at', 'approved_by'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'tax_exempt', 'contact_email', 'contact_phone', 'processing_fee_override', 'stripe_onboarded', 'fee_collection_method', 'approved_at', 'approved_by', 'admin_notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('organization');
    }

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            if (! $organization->public_id) {
                $organization->public_id = PublicIdGenerator::generate(Organization::class);
            }

            if (! $organization->code) {
                $organization->code = static::generateUniqueCode();
            }
        });

        static::forceDeleting(function (Organization $organization) {
            if (filled($organization->logo_path)) {
                Storage::disk('public')->delete($organization->logo_path);
            }

            foreach ($organization->documents as $document) {
                if (filled($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }
            }

            $organization->users()->delete();
            $organization->campaigns()->delete();
            $organization->elements()->delete();
            $organization->processingFees()->delete();
            $organization->documents()->delete();
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function logoUrl(): string
    {
        if (! filled($this->logo_path)) {
            return '';
        }

        if (Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return route('organization.logo', $this);
    }

    public function chipBrandId(): ?string
    {
        return $this->chip_brand_id;
    }

    public function chipApiKey(): ?string
    {
        return $this->chip_api_key;
    }

    public function chipPaymentMethods(): array
    {
        return $this->settings['chip_payment_methods'] ?? ['card'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrganizationDocument::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function elements(): HasMany
    {
        return $this->hasMany(Element::class);
    }

    public function processingFees(): HasMany
    {
        return $this->hasMany(ProcessingFee::class);
    }

    protected function chipOnboarded(): Attribute
    {
        return Attribute::make(
            get: fn () => filled($this->chip_brand_id) && filled($this->chip_api_key),
        );
    }

    protected function chipActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->chip_onboarded && $this->chip_enabled,
        );
    }

    protected function stripeActive(): Attribute
    {
        return Attribute::make(
            get: fn () => (bool) $this->stripe_onboarded && $this->stripe_enabled,
        );
    }

    /**
     * @return string[]
     */
    public function chipPaymentMethodWhitelist(): array
    {
        return PaymentMethodWhitelistMapper::map($this->chipPaymentMethods());
    }

    public function trackingConfigurations(): HasMany
    {
        return $this->hasMany(TrackingConfiguration::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'stripe_onboarded' => 'boolean',
            'stripe_onboarded_at' => 'datetime',
            'stripe_enabled' => 'boolean',
            'chip_enabled' => 'boolean',
            'tax_exempt' => 'boolean',
            'approved_at' => 'datetime',
            'status' => OrganizationStatus::class,
            'chip_api_key' => 'encrypted',
            'chip_webhook_public_key' => 'encrypted',
        ];
    }
}
