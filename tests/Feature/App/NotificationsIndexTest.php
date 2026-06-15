<?php

declare(strict_types=1);

use App\Livewire\App\Notifications\Index;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdminToOrgAdminNotification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('renders the notifications inbox', function () {
    $this->user->notify(new AdminToOrgAdminNotification(
        message: 'Platform maintenance scheduled.',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
        type: 'warning',
    ));

    actingAs($this->user)
        ->get('/app/notifications')
        ->assertOk()
        ->assertSee('Notifications')
        ->assertSee('Platform maintenance scheduled.');
});

it('only shows admin-to-org notifications', function () {
    $this->user->notify(new AdminToOrgAdminNotification(
        message: 'Visible notification',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    actingAs($this->user)->get('/app/notifications')->assertSee('Visible notification');
});

it('marks a notification as read', function () {
    actingAs($this->user);

    $this->user->notify(new AdminToOrgAdminNotification(
        message: 'Mark me as read',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    $notification = $this->user->notifications()->first();

    expect($notification->unread())->toBeTrue();

    Livewire::test(Index::class)
        ->call('markAsRead', $notification->id)
        ->assertHasNoErrors();

    expect($notification->fresh()->unread())->toBeFalse();
});

it('deletes a notification', function () {
    actingAs($this->user);

    $this->user->notify(new AdminToOrgAdminNotification(
        message: 'Delete me',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    $notification = $this->user->notifications()->first();

    Livewire::test(Index::class)
        ->call('delete', $notification->id)
        ->assertHasNoErrors();

    expect($this->user->notifications()->count())->toBe(0);
});

it('does not affect another users notification', function () {
    actingAs($this->user);

    $otherUser = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $otherUser->notify(new AdminToOrgAdminNotification(
        message: 'Other user notification',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    Livewire::test(Index::class)
        ->call('delete', $otherUser->notifications()->first()->id)
        ->assertHasNoErrors();

    expect($otherUser->notifications()->count())->toBe(1);
});

it('shows the unread count in the topbar', function () {
    $this->user->notify(new AdminToOrgAdminNotification(
        message: 'Unread notification',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('1');
});

it('redirects guests from the inbox', function () {
    get('/app/notifications')->assertRedirect('/login');
});
