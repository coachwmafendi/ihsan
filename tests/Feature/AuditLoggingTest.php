<?php

use App\Listeners\LogArtisanCommand;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

test('creating a model writes an audit log entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $organization = Organization::factory()->create(['name' => 'Audit Test Org']);

    $log = Activity::query()
        ->where('log_name', 'model')
        ->where('event', 'created')
        ->where('subject_type', Organization::class)
        ->where('subject_id', $organization->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_type)->toBe(User::class);
    expect($log->causer_id)->toBe($user->id);
    expect($log->getProperty('attributes.name'))->toBe('Audit Test Org');
});

test('updating a model writes an audit log entry', function () {
    $organization = Organization::factory()->create(['name' => 'Original Name']);

    $organization->update(['name' => 'Updated Name']);

    $log = Activity::query()
        ->where('log_name', 'model')
        ->where('event', 'updated')
        ->where('subject_type', Organization::class)
        ->where('subject_id', $organization->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->getProperty('changes.name'))->toBe('Updated Name');
});

test('deleting a model writes an audit log entry', function () {
    $organization = Organization::factory()->create();
    $id = $organization->id;

    $organization->delete();

    $log = Activity::query()
        ->where('log_name', 'model')
        ->where('event', 'deleted')
        ->where('subject_type', Organization::class)
        ->where('subject_id', $id)
        ->first();

    expect($log)->not->toBeNull();
});

test('artisan command listener skips noisy scheduler and queue commands', function () {
    $listener = new LogArtisanCommand;

    foreach (['schedule:run', 'queue:work'] as $command) {
        $listener->handle(new CommandStarting(
            $command,
            new ArrayInput(['command' => $command]),
            new BufferedOutput,
        ));
    }

    expect(Activity::query()->where('log_name', 'artisan')->count())->toBe(0);
});

test('artisan command listener does not fail when the activity table is missing', function () {
    Schema::drop('activity_log');

    $listener = new LogArtisanCommand;

    $event = new CommandStarting(
        'route:list',
        new ArrayInput(['command' => 'route:list']),
        new BufferedOutput,
    );

    expect(fn () => $listener->handle($event))->not->toThrow(QueryException::class);
});

test('artisan command listener writes an audit log entry', function () {
    $listener = new LogArtisanCommand;

    $event = new CommandStarting(
        'route:list',
        new ArrayInput(['command' => 'route:list']),
        new BufferedOutput,
    );

    $listener->handle($event);

    $log = Activity::query()
        ->where('log_name', 'artisan')
        ->where('event', 'command_starting')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->getProperty('command'))->toBe('route:list');
    expect($log->getProperty('user'))->not->toBeNull();
});
