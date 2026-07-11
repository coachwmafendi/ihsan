<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\EmailSettings;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('logs email settings changes to activity log', function () {
    Setting::set('mail_driver', 'ses');
    Setting::set('ses_key', 'old-key');
    Setting::set('ses_secret', 'old-secret');
    Setting::set('ses_region', 'us-east-1');
    Setting::set('mail_from_address', 'no-reply@getihsan.my');
    Setting::set('mail_from_name', 'Ihsan');
    Setting::set('mail_throttle_seconds', 0);

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($admin)
        ->test(EmailSettings::class)
        ->set('data.ses_region', 'ap-southeast-1')
        ->call('save');

    $activity = Activity::query()
        ->where('description', 'Email settings updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($admin->id);
    expect($activity->properties['old']['ses_region'])->toBe('us-east-1');
    expect($activity->properties['new']['ses_region'])->toBe('ap-southeast-1');
});
