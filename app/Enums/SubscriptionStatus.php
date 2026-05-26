<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SubscriptionStatus: string implements HasLabel
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case PastDue = 'past_due';
    case Incomplete = 'incomplete';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Cancelled => 'Cancelled',
            self::PastDue => 'Past Due',
            self::Incomplete => 'Incomplete',
        };
    }
}
