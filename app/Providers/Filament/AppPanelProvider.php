<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\StripeOnboarding;
use App\Filament\Pages\Auth\EditProfile;
use App\Http\Middleware\RedirectIfStripeNotOnboarded;
use Filament\Enums\GlobalSearchPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('240px')
            ->brandLogo(asset('logo-ihsan.png'))
            ->brandLogoHeight('2rem')
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->profile(EditProfile::class, isSimple: false)
            ->homeUrl(fn (): string => route('filament.app.pages.insights'))
            ->darkMode()
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Teal,
            ])
            ->font('Manrope')
            ->maxContentWidth('7xl')
            ->viteTheme('resources/css/filament/app/theme.css')
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                StripeOnboarding::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RedirectIfStripeNotOnboarded::class,
            ]);
    }
}
