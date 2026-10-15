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

it('sets the organisation name in bold', function () {
    actingAs($this->user);

    $html = Livewire::test(Topbar::class)->html();

    expect($html)->toMatch('/<span class="[^"]*\bfont-bold\b[^"]*">\s*'.preg_quote(e($this->organization->name), '/').'\s*<\/span>/');
});

it('links the organisation to its settings', function () {
    actingAs($this->user);

    Livewire::test(Topbar::class)
        ->assertSeeHtml('href="'.route('app.settings.organization').'"');
});

it('shows the organisation logo beside its name', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logos/org.png', 'png');
    $this->organization->update(['logo_path' => 'logos/org.png']);

    actingAs($this->user);

    Livewire::test(Topbar::class)
        ->assertSeeHtml('src="'.Storage::disk('public')->url('logos/org.png').'"');
});

/**
 * Three organisations have no logo. A blank gap where the logo should be
 * reads as a broken image, so they get their initials instead.
 */
it('shows the organisation initials when there is no logo', function () {
    $this->organization->update(['name' => 'Masjid Tahfiz Al Ayubi', 'logo_path' => null]);

    actingAs($this->user);

    Livewire::test(Topbar::class)
        ->assertSeeHtml('>MT</span>');
});

it('hides the badge when there are no unread notifications', function () {
    actingAs($this->user);

    Livewire::test(Topbar::class)
        ->assertSet('unreadCount', 0)
        ->assertSeeHtml('hidden');
});
