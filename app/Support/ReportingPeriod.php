<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Resolves the named periods behind the app's date filters.
 *
 * Timestamps are stored in UTC while every organization reads them in
 * Malaysian time, so a day has to be measured locally and only then expressed
 * as the UTC instants the queries compare against. Measuring it in UTC made
 * "Today" run from 8am to 8am local, which hid a donation taken at 7am.
 */
final class ReportingPeriod
{
    public const DisplayTimezone = 'Asia/Kuala_Lumpur';

    /**
     * Now, in the timezone the figures are read in.
     */
    public static function localNow(): CarbonImmutable
    {
        return CarbonImmutable::now(self::DisplayTimezone);
    }

    /**
     * The named period as local day boundaries.
     *
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    public static function local(string $period, ?string $customFrom = null, ?string $customTo = null): array
    {
        $now = self::localNow();

        if ($period === 'custom') {
            return self::custom($customFrom, $customTo);
        }

        return match ($period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'yesterday' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            '7_days' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            '14_days' => [$now->subDays(13)->startOfDay(), $now->endOfDay()],
            '30_days' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            '90_days' => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            'this_week' => [$now->startOfWeek(), $now->endOfWeek()],
            'this_month' => [$now->startOfMonth(), $now->endOfMonth()],
            'this_year' => [$now->startOfYear(), $now->endOfYear()],
            'last_week' => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'last_month' => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            'last_year' => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            default => [null, null],
        };
    }

    /**
     * The named period as UTC instants, ready to compare against stored
     * timestamps.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function utc(string $period, ?string $customFrom = null, ?string $customTo = null): array
    {
        return self::toUtc(self::local($period, $customFrom, $customTo));
    }

    /**
     * @param  array{0: ?CarbonImmutable, 1: ?CarbonImmutable}  $range
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function toUtc(array $range): array
    {
        [$from, $to] = $range;

        return [
            $from === null ? null : Carbon::instance($from->utc()),
            $to === null ? null : Carbon::instance($to->utc()),
        ];
    }

    /**
     * A single local day as the UTC instants that bound it.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dayInUtc(CarbonImmutable $localDay): array
    {
        return [
            Carbon::instance($localDay->startOfDay()->utc()),
            Carbon::instance($localDay->endOfDay()->utc()),
        ];
    }

    /**
     * A Y-m-d string, read as a local day rather than a UTC one.
     */
    public static function parseLocalDate(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::DisplayTimezone);
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private static function custom(?string $from, ?string $to): array
    {
        $start = $from ? self::parseLocalDate($from)->startOfDay() : null;
        $end = $to ? self::parseLocalDate($to)->endOfDay() : null;

        if ($start !== null && $end !== null && $start->isAfter($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }
}
