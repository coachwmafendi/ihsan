<?php

declare(strict_types=1);

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Models\Activity;

final class ModelActivityObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'updated', $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted');
    }

    public function forceDeleted(Model $model): void
    {
        $this->log($model, 'force_deleted');
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function log(Model $model, string $event, array $changes = []): void
    {
        $hidden = $model->getHidden();
        $modelClass = $model::class;

        /** @var Activity $activity */
        $activity = activity()
            ->useLog('model')
            ->event($event)
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->withProperties([
                'model' => $modelClass,
                'attributes' => Arr::except($model->toArray(), $hidden),
                'changes' => Arr::except($changes, $hidden),
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ])
            ->log("{$modelClass} {$event}");

        $activity->save();
    }
}
