<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Resolves the named periods behind the app's date filters.
 *
 * Timestamps are stored in UTC while each organization reads them on its own
 * clock, so a day has to be measured locally and only then expressed as the
 * UTC instants the queries compare against. Measuring it in UTC made "Today"
 * run from 8am to 8am in Malaysia, which hid a donation taken at 7am.
 *
 * Build one with for() when an organization is in scope, and platform() for
 * the admin panel, which spans every organization at once.
 */
final readonly class ReportingPeriod
{
    /**
     * Used by the admin panel, and by any organization that has not chosen
     * its own. Every organization on the platform so far is UTC+8.
     */
    public const DefaultTimezone = 'Asia/Kuala_Lumpur';

    public function __construct(public string $timezone = self::DefaultTimezone) {}

    /**
     * The period as read by one organization.
     */
    public static function for(?Organization $organization): self
    {
        return new self($organization?->reportingTimezone() ?? self::DefaultTimezone);
    }

    /**
     * The period as read by the platform operators, whose figures span every
     * organization and so cannot follow any single one's clock.
     */
    public static function platform(): self
    {
        return new self;
    }

    /**
     * Now, in the timezone the figures are read in.
     */
    public function localNow(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone);
    }

    /**
     * The named period as local day boundaries.
     *
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    public function local(string $period, ?string $customFrom = null, ?string $customTo = null): array
    {
        $now = $this->localNow();

        if ($period === 'custom') {
            return $this->custom($customFrom, $customTo);
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
    public function utc(string $period, ?string $customFrom = null, ?string $customTo = null): array
    {
        return $this->toUtc($this->local($period, $customFrom, $customTo));
    }

    /**
     * @param  array{0: ?CarbonImmutable, 1: ?CarbonImmutable}  $range
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function toUtc(array $range): array
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
    public function dayInUtc(CarbonImmutable $localDay): array
    {
        return [
            Carbon::instance($localDay->startOfDay()->utc()),
            Carbon::instance($localDay->endOfDay()->utc()),
        ];
    }

    /**
     * A Y-m-d string, read as a local day rather than a UTC one.
     */
    public function parseLocalDate(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, $this->timezone);
    }

    /**
     * A stored UTC timestamp, read on the local clock.
     */
    public function toLocal(CarbonImmutable|string $utcTimestamp): CarbonImmutable
    {
        $timestamp = $utcTimestamp instanceof CarbonImmutable
            ? $utcTimestamp
            : CarbonImmutable::parse($utcTimestamp, 'UTC');

        return $timestamp->setTimezone($this->timezone);
    }

    /**
     * How the timezone is named to the people reading the figures, e.g.
     * "Kuala Lumpur (UTC+8)".
     *
     * Built from the zone's own city and offset rather than PHP's abbreviation,
     * which returns "+08" for most of the region instead of anything a reader
     * would recognise.
     */
    public function label(): string
    {
        if ($this->timezone === 'UTC') {
            return 'UTC';
        }

        $city = str_replace('_', ' ', (string) last(explode('/', $this->timezone)));

        return $city.' (UTC'.$this->offsetLabel().')';
    }

    /**
     * The current offset, written the way people say it: +8, not +08:00. Half
     * hour zones keep their minutes.
     */
    private function offsetLabel(): string
    {
        [$hours, $minutes] = explode(':', $this->localNow()->format('P'));

        $hours = $hours[0].ltrim(substr($hours, 1), '0');

        return $minutes === '00' ? $hours : $hours.':'.$minutes;
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function custom(?string $from, ?string $to): array
    {
        $start = $from ? $this->parseLocalDate($from)->startOfDay() : null;
        $end = $to ? $this->parseLocalDate($to)->endOfDay() : null;

        if ($start !== null && $end !== null && $start->isAfter($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }
}
