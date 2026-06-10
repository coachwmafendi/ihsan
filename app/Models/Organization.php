<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
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
 * @property bool $stripe_onboarded
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
#[Fillable(['name', 'code', 'ros_rob_number', 'registration_type', 'description', 'logo_path', 'website_url', 'contact_email', 'contact_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postcode', 'country', 'sector', 'tax_exempt', 'processing_fee_override', 'admin_notes', 'status', 'stripe_account_id', 'stripe_onboarded', 'stripe_onboarded_at', 'bank_account_name', 'bank_account_number', 'bank_name', 'settings', 'fee_collection_method', 'approved_at', 'approved_by'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, LogsActivity;

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
            if (! $organization->code) {
                $organization->code = static::generateUniqueCode();
            }
        });

        static::deleting(function (Organization $organization) {
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
        } while (static::where('code', $code)->exists());

        return $code;
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

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'stripe_onboarded' => 'boolean',
            'stripe_onboarded_at' => 'datetime',
            'tax_exempt' => 'boolean',
            'approved_at' => 'datetime',
            'status' => OrganizationStatus::class,
        ];
    }
}
