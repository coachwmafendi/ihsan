<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->for($this->organization)->create();
    Campaign::factory()->for($this->organization)->count(2)->create();
});

it('uses wire:navigate on internal sidebar links', function (string $path) {
    $response = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk();

    expect(preg_match(
        '/<a\s+href="'.preg_quote($path, '/').'"[\s\S]*?wire:navigate/',
        $response->getContent()
    ))->toBe(1, "Expected {$path} sidebar link to use wire:navigate.");
})->with([
    '/dashboard',
    '/campaigns',
    '/elements',
    '/donations',
    '/recurring-plans',
    '/supporters',
    '/audit-log',
    '/reports/monthly-donations',
    '/settings/organization',
    '/settings/payment',
    '/settings/account',
    '/payouts',
]);

it('displays the application version in the sidebar', function () {
    $response = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk();

    $version = config('app.version', '1.0.0');

    expect($response->getContent())
        ->toContain('<span class="font-medium text-slate-500">Ihsan</span>')
        ->toContain("v{$version}");
});

it('does not use wire:navigate on external sidebar links', function () {
    $response = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk();

    $html = $response->getContent();

    preg_match_all('/<a\s+href="\/virtual-terminal"[^>]*>/', $html, $matches);

    expect($matches[0][0] ?? '')->not->toContain('wire:navigate');
});

it('shows the app logo in the collapsed sidebar toggle, swapping to the panel icon on hover', function () {
    $html = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->getContent();

    // Isolate the desktop sidebar header's collapse toggle button.
    expect(preg_match(
        '/<button\b(?:(?!<\/button>).)*?:aria-label="\$store\.sidebar\.collapsed \? \x27Expand sidebar\x27 : \x27Collapse sidebar\x27"(?:(?!<\/button>).)*?<\/button>/s',
        $html,
        $matches
    ))->toBe(1, 'Expected the desktop sidebar collapse toggle button to be rendered.');

    $button = $matches[0];

    expect($button)
        ->toContain('group')
        // The logo only renders while collapsed, and fades out on hover/focus.
        ->toContain('x-show="$store.sidebar.collapsed"')
        ->toContain('group-hover:opacity-0')
        ->toContain('group-focus-visible:opacity-0')
        ->toContain('aria-label="Ihsan"')
        // The panel icon is hidden while collapsed until the toggle is hovered/focused.
        ->toContain("collapsed ? 'opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100' : 'opacity-100'");
});

it('animates the sidebar only while a toggle is running', function () {
    $html = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->getContent();

    // The store flags the toggle, and app.css scopes every layout transition
    // to .sidebar-animating so a wire:navigate visit cannot replay them.
    expect($html)
        ->toContain("root.classList.add('sidebar-animating')")
        ->toContain("root.classList.remove('sidebar-animating')");

    expect(preg_match('/<div\b[^>]*id="app-sidebar-desktop"[^>]*>/', $html, $sidebar))
        ->toBe(1, 'Expected the desktop sidebar to be rendered.');

    expect(preg_match('/<div\b[^>]*id="app-content"[^>]*>/', $html, $content))
        ->toBe(1, 'Expected the app content wrapper to be rendered.');

    // No unscoped layout transition may survive on the shell, or every
    // navigation animates again.
    expect($sidebar[0])->not->toContain('transition-[width]');
    expect($content[0])->not->toContain('transition-[padding]');
});

it('keeps layout transitions out of the sidebar markup', function () {
    $html = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->getContent();

    expect(preg_match('/<div\b[^>]*id="app-sidebar-desktop".*?<\/nav>/s', $html, $sidebar))
        ->toBe(1, 'Expected the desktop sidebar nav to be rendered.');

    expect($sidebar[0])
        ->not->toContain('transition-[padding]')
        ->not->toContain('transition-[gap]')
        ->not->toContain('transition-[color,background-color,padding,gap]');
});

it('fades sidebar item labels instead of popping them in', function () {
    $html = $this->actingAs($this->user)
        ->get('https://app.example.test/dashboard')
        ->assertOk()
        ->getContent();

    expect(preg_match(
        '/<span\b(?:(?!<\/span>).)*?x-show="! \$store\.sidebar\.collapsed"(?:(?!<\/span>).)*?>Campaigns<\/span>/s',
        $html,
        $matches
    ))->toBe(1, 'Expected the Campaigns sidebar label to be rendered.');

    expect($matches[0])
        ->toContain('x-transition:enter="transition-opacity ease-out duration-200 delay-150')
        ->toContain('x-transition:enter-start="opacity-0"')
        ->toContain('x-transition:leave="transition-opacity ease-in duration-100');
});

it('arms the sidebar transitions on every app page, not just the dashboard', function (string $path) {
    $html = $this->actingAs($this->user)
        ->get('https://app.example.test'.$path)
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('id="app-sidebar-desktop"')
        ->toContain("root.classList.add('sidebar-animating')")
        ->not->toContain("animate ? 'transition-[width]");
})->with([
    '/dashboard',
    '/campaigns',
    '/elements',
    '/donations',
    '/recurring-plans',
    '/supporters',
    '/payouts',
    '/audit-log',
    '/reports/monthly-donations',
    '/settings/organization',
    '/settings/account',
    '/settings/notifications',
    '/notifications',
]);
