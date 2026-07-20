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
    case IncompleteExpired = 'incomplete_expired';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Cancelled => 'Cancelled',
            self::PastDue => 'Retrying',
            self::Incomplete => 'Incomplete',
            self::IncompleteExpired => 'Incomplete Expired',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }
}
