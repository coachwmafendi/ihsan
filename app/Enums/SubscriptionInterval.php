<?php

namespace App\Enums;

enum SubscriptionInterval: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Bimonthly = 'bimonthly';
    case Quarterly = 'quarterly';
    case Semiannual = 'semiannual';
    case Yearly = 'yearly';

    public function shortLabel(?string $locale = null): string
    {
        return match ($this) {
            self::Weekly => trans('emails.donor_recurring_payment.upgrade_interval_short_weekly', [], $locale),
            self::Biweekly => trans('emails.donor_recurring_payment.upgrade_interval_short_biweekly', [], $locale),
            self::Monthly => trans('emails.donor_recurring_payment.upgrade_interval_short_monthly', [], $locale),
            self::Bimonthly => trans('emails.donor_recurring_payment.upgrade_interval_short_bimonthly', [], $locale),
            self::Quarterly => trans('emails.donor_recurring_payment.upgrade_interval_short_quarterly', [], $locale),
            self::Semiannual => trans('emails.donor_recurring_payment.upgrade_interval_short_semiannual', [], $locale),
            self::Yearly => trans('emails.donor_recurring_payment.upgrade_interval_short_yearly', [], $locale),
        };
    }
}
