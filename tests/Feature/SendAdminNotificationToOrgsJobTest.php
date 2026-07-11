<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Jobs\SendAdminNotificationToOrgs;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdminToOrgAdminNotification;
use Illuminate\Notifications\DatabaseNotification;

it('creates database notifications for organization admins', function () {
    $organization = Organization::factory()->create();
    $adminOne = User::factory()->create([
        'role' => UserRole::NgoAdmin,
        'organization_id' => $organization->id,
    ]);
    $adminTwo = User::factory()->create([
        'role' => UserRole::NgoAdmin,
        'organization_id' => $organization->id,
    ]);
    $nonAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin,
        'organization_id' => $organization->id,
    ]);

    $job = new SendAdminNotificationToOrgs(
        message: 'Hello admins',
        senderName: 'Platform Admin',
        organizationIds: [$organization->id],
        type: 'warning',
        image: 'notifications/image.png',
    );

    $job->handle();

    expect(DatabaseNotification::query()->where('type', AdminToOrgAdminNotification::class)->count())->toBe(2);
    expect($adminOne->notifications()->count())->toBe(1);
    expect($adminTwo->notifications()->count())->toBe(1);
    expect($nonAdmin->notifications()->count())->toBe(0);

    $notification = $adminOne->notifications()->first();

    expect($notification->data['message'])->toBe('Hello admins');
    expect($notification->data['type'])->toBe('warning');
    expect($notification->data['image'])->toBe('notifications/image.png');
});
