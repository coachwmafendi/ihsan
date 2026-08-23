<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Shared handling for the Activity card on a record page.
 *
 * Only the tail of a history fits on a page, so the newest entries are the ones
 * kept — then flipped, because a single record's history reads as a story from
 * the start rather than as a feed.
 */
trait ShowsActivityTimeline
{
    private const ACTIVITY_TIMELINE_LIMIT = 15;

    /**
     * @param  Builder<Activity>  $query  ordered newest first
     * @return Collection<int, Activity>
     */
    protected function activityTimeline(Builder $query): Collection
    {
        return $query
            ->limit(self::ACTIVITY_TIMELINE_LIMIT)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * @param  Builder<Activity>  $query
     */
    protected function activityTimelineHasMore(Builder $query): bool
    {
        return $query->count() > self::ACTIVITY_TIMELINE_LIMIT;
    }
}
