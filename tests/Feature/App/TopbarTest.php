<?php

declare(strict_types=1);

use App\Livewire\App\Topbar;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdminToOrgAdminNotification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('shows the unread notification count badge anchored to the bell icon', function () {
    $this->user->notify(new AdminToOrgAdminNotification(
        message: 'Unread notification',
        senderName: 'Platform Admin',
        organizationId: $this->organization->id,
    ));

    actingAs($this->user);

    Livewire::test(Topbar::class)
        ->assertSet('unreadCount', 1)
        ->assertSeeHtml('id="notification-badge"')
        ->assertSeeHtml('relative inline-flex items-center justify-center');
});

it('hides the badge when there are no unread notifications', function () {
    actingAs($this->user);

    Livewire::test(Topbar::class)
        ->assertSet('unreadCount', 0)
        ->assertSeeHtml('hidden');
});
