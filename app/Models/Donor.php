<?php

namespace App\Models;

use Database\Factories\DonorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'stripe_customer_id', 'magic_token', 'magic_token_expires_at'])]
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
            'magic_token' => $token,
            'magic_token_expires_at' => now()->addHours(24),
        ]);

        return $token;
    }

    protected static function booted(): void
    {
        static::creating(function (Donor $donor) {
            if (! $donor->magic_token) {
                $donor->magic_token = Str::random(64);
                $donor->magic_token_expires_at = now()->addYears(5);
            }
        });
    }

    public function isValidMagicToken(string $token): bool
    {
        return $this->magic_token === $token
            && $this->magic_token_expires_at !== null
            && $this->magic_token_expires_at->isFuture();
    }

    protected function casts(): array
    {
        return [
            'magic_token_expires_at' => 'datetime',
        ];
    }
}
