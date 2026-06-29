<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentGateway: string implements HasLabel
{
    case Stripe = 'stripe';
    case Chip = 'chip';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::Chip => 'Chip',
        };
    }
}
