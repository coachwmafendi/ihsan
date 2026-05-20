<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentGateway: string implements HasLabel
{
    case Stripe = 'stripe';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
        };
    }
}
