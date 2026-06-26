<?php

namespace App\Services;

use App\Enums\SubscriptionInterval;
use Carbon\CarbonImmutable;

class SubscriptionSchedule
{
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
