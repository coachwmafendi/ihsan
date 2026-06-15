<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Admin\Pages\SendNotificationToOrgs;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdminToOrgAdminNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]);
    $this->ngoAdmin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);
});

it('renders the send notification page for super admins', function () {
    actingAs($this->superAdmin)
        ->get('/admin/send-notification-to-orgs')
        ->assertOk()
        ->assertSee('Send Notification to Organization Admins');
});

it('denies access to non super admins', function () {
    actingAs($this->ngoAdmin)
        ->get('/admin/send-notification-to-orgs')
        ->assertForbidden();
});

it('deletes all admin to organization notifications', function () {
    $this->ngoAdmin->notify(new AdminToOrgAdminNotification(
        message: 'First message',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));
    $this->ngoAdmin->notify(new AdminToOrgAdminNotification(
        message: 'Second message',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    expect(DatabaseNotification::where('type', AdminToOrgAdminNotification::class)->count())->toBe(2);

    actingAs($this->superAdmin);

    Livewire::test(SendNotificationToOrgs::class)
        ->call('deleteAllNotifications')
        ->assertHasNoErrors();

    expect(DatabaseNotification::where('type', AdminToOrgAdminNotification::class)->count())->toBe(0);
});

it('deletes only admin to organization notification records', function () {
    $this->ngoAdmin->notify(new AdminToOrgAdminNotification(
        message: 'Admin message',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'notifiable_type' => User::class,
        'notifiable_id' => $this->ngoAdmin->id,
        'type' => 'other-notification',
        'data' => ['message' => 'Other'],
    ]);

    expect(DatabaseNotification::count())->toBe(2);

    actingAs($this->superAdmin);

    Livewire::test(SendNotificationToOrgs::class)
        ->call('deleteAllNotifications')
        ->assertHasNoErrors();

    expect(DatabaseNotification::where('type', AdminToOrgAdminNotification::class)->count())->toBe(0);
    expect(DatabaseNotification::count())->toBe(1);
});
