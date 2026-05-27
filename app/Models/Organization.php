<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'code', 'ros_rob_number', 'registration_type', 'description', 'logo_path', 'website_url', 'contact_email', 'contact_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postcode', 'country', 'sector', 'tax_exempt', 'processing_fee_override', 'admin_notes', 'status', 'stripe_account_id', 'stripe_onboarded', 'stripe_onboarded_at', 'bank_account_name', 'bank_account_number', 'bank_name', 'settings', 'fee_collection_method', 'approved_at', 'approved_by'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

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
