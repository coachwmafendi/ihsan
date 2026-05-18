<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'ros_rob_number', 'registration_type', 'description', 'logo_path', 'website_url', 'contact_email', 'contact_phone', 'status', 'stripe_account_id', 'stripe_onboarded', 'bank_account_name', 'bank_account_number', 'bank_name', 'settings', 'approved_at', 'approved_by'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

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

    public function platformFees(): HasMany
    {
        return $this->hasMany(PlatformFee::class);
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'stripe_onboarded' => 'boolean',
            'approved_at' => 'datetime',
            'status' => OrganizationStatus::class,
        ];
    }
}
