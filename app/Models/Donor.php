<?php

namespace App\Models;

use App\Services\PublicIdGenerator;
use Carbon\CarbonImmutable;
use Database\Factories\DonorFactory;
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
 * @property string $email
 * @property string|null $phone
 * @property string|null $stripe_customer_id
 * @property string|null $magic_token
 * @property CarbonImmutable|null $magic_token_expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $address_city
 * @property string|null $address_state
 * @property string|null $address_postal_code
 * @property string|null $country
 * @property string|null $locale
 * @property string|null $photo_path
 * @property string|null $title
 * @property string|null $occupation
 * @property string|null $public_id
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read Collection<int, Donation> $donations
 * @property-read int|null $donations_count
 * @property-read string|null $photo_url
 * @property-read Collection<int, DonorPaymentMethod> $paymentMethods
 * @property-read int|null $payment_methods_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 *
 * @method static \Database\Factories\DonorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereAddressCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereAddressPostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereAddressState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereMagicToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereMagicTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor wherePublicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereStripeCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Donor whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['public_id', 'name', 'email', 'phone', 'title', 'occupation', 'stripe_customer_id', 'magic_token', 'magic_token_expires_at', 'address_line1', 'address_line2', 'address_city', 'address_state', 'address_postal_code', 'country', 'locale', 'photo_path'])]
class Donor extends Model
{
    /** @use HasFactory<DonorFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'title', 'occupation', 'address_line1', 'address_line2', 'address_city', 'address_state', 'address_postal_code', 'country'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('donor');
    }

    protected static function booted(): void
    {
        static::creating(function (Donor $donor) {
            if (! $donor->public_id) {
                $donor->public_id = PublicIdGenerator::generate(static::class);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(DonorPaymentMethod::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(DonorEmailLog::class)->latest('sent_at');
    }

    public function generateMagicToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'magic_token' => hash('sha256', $token),
            'magic_token_expires_at' => now()->addHours(24),
        ]);

        return $token;
    }

    public function isValidMagicToken(string $token): bool
    {
        return $this->magic_token === hash('sha256', $token)
            && $this->magic_token_expires_at !== null
            && $this->magic_token_expires_at->isFuture();
    }

    protected function casts(): array
    {
        return [
            'magic_token_expires_at' => 'datetime',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path === null) {
            return null;
        }

        return route('donor.photo', $this);
    }
}
