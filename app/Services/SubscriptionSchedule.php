<?php

namespace App\Services;

use App\Enums\SubscriptionInterval;
use Carbon\CarbonImmutable;

class SubscriptionSchedule
{
    public static function monthlyFactor(SubscriptionInterval $interval): float
    {
        return match ($interval) {
            SubscriptionInterval::Weekly => 52 / 12,
            SubscriptionInterval::Biweekly => 26 / 12,
            SubscriptionInterval::Monthly => 1.0,
            SubscriptionInterval::Bimonthly => 0.5,
            SubscriptionInterval::Quarterly => 1 / 3,
            SubscriptionInterval::Semiannual => 1 / 6,
            SubscriptionInterval::Yearly => 1 / 12,
        };
    }

    public static function nextChargeAt(
        CarbonImmutable $from,
        SubscriptionInterval $interval,
    ): CarbonImmutable {
        return match ($interval) {
            SubscriptionInterval::Weekly => $from->addWeek(),
            SubscriptionInterval::Biweekly => $from->addWeeks(2),
            SubscriptionInterval::Monthly => self::addMonthsClamped($from, 1),
            SubscriptionInterval::Bimonthly => self::addMonthsClamped($from, 2),
            SubscriptionInterval::Quarterly => self::addMonthsClamped($from, 3),
            SubscriptionInterval::Semiannual => self::addMonthsClamped($from, 6),
            SubscriptionInterval::Yearly => self::addMonthsClamped($from, 12),
        };
    }

    private static function addMonthsClamped(CarbonImmutable $from, int $months): CarbonImmutable
    {
        $next = $from->addMonthsNoOverflow($months);

        $expectedDay = min((int) $from->format('j'), (int) $next->endOfMonth()->format('j'));

        return $next->setDay($expectedDay);
    }
}
