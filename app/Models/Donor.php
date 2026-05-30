<?php

namespace App\Models;

use Database\Factories\DonorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'title', 'occupation', 'stripe_customer_id', 'magic_token', 'magic_token_expires_at', 'address_line1', 'address_line2', 'address_city', 'address_state', 'address_postal_code', 'country', 'locale', 'photo_path'])]
class Donor extends Model
{
    /** @use HasFactory<DonorFactory> */
    use HasFactory;

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
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
