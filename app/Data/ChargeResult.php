<?php

namespace App\Data;

use App\Models\Donation;

readonly class ChargeResult
{
    public function __construct(
        public string $status,
        public ?Donation $donation = null,
        public ?string $errorCode = null,
        public ?string $clientSecret = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function requiresAction(): bool
    {
        return $this->status === 'requires_action';
    }

    public function failed(): bool
    {
        return $this->status === 'failed';
    }
}
