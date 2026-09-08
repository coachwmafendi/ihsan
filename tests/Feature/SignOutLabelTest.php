<?php

declare(strict_types=1);

/**
 * The auth screens say "Sign in", so the way out says "Sign out" - and the
 * icon shows an arrow leaving the frame. The one that was there points into
 * it, which is the gesture for going the other way.
 */
it('says sign out wherever the way out is offered', function (string $view) {
    $markup = file_get_contents(base_path($view));

    expect($markup)->not->toContain('Log out');
})->with([
    'resources/views/components/topbar.blade.php',
    'resources/views/livewire/app/topbar.blade.php',
    'resources/views/components/desktop-user-menu.blade.php',
    'resources/views/layouts/app/sidebar.blade.php',
    'resources/views/pages/auth/verify-email.blade.php',
]);

it('points the arrow out of the frame, not into it', function (string $view) {
    $markup = file_get_contents(base_path($view));

    expect($markup)->not->toContain('arrow-left-on-rectangle')
        ->and($markup)->not->toContain('ArrowLeftEndOnRectangle');
})->with([
    'resources/views/components/topbar.blade.php',
    'resources/views/livewire/app/topbar.blade.php',
    'resources/views/vendor/filament-panels/widgets/account-widget.blade.php',
]);

it('overrides the icon the admin panel ships with', function () {
    // Filament's own default is the inward arrow, and it is set in the package
    // rather than in a view we can edit.
    $provider = file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'));

    expect($provider)
        ->toContain('PanelsIconAlias::USER_MENU_LOGOUT_BUTTON')
        ->toContain('Heroicon::ArrowRightStartOnRectangle');
});
