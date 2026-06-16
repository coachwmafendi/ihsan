<?php

namespace App\Enums;

enum TrackingProviderStatus: string
{
    case Active = 'active';
    case Error = 'error';
    case NotConfigured = 'not_configured';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Error => 'Error',
            self::NotConfigured => 'Not configured',
            self::Disabled => 'Disabled',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-400',
            self::Error => 'bg-red-400',
            self::NotConfigured => 'bg-slate-300',
            self::Disabled => 'bg-slate-300',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Error => 'bg-red-50 text-red-700 ring-red-600/20',
            self::NotConfigured => 'bg-slate-50 text-slate-500 ring-slate-500/10',
            self::Disabled => 'bg-slate-50 text-slate-500 ring-slate-500/10',
        };
    }
}
