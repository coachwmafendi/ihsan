<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\SendNotificationToOrgs;
use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\FraudPrevention;
use App\Filament\Pages\MonthlyInvoices;
use App\Filament\Pages\PlatformOverview;
use App\Filament\Pages\ProcessingFees;
use App\Filament\Pages\Revenue;
use App\Filament\Pages\Transactions;
use App\Filament\Widgets\DonationTrendChart;
use App\Filament\Widgets\PaymentMethodChart;
use App\Filament\Widgets\ProcessingFeeTrendChart;
use App\Filament\Widgets\TopOrganizationsChart;
use App\Http\Controllers\RevenueReportController;
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
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $domain = config('app.admin_panel_domain');

        return $panel
            ->default()
            ->id('admin')
            ->domain($domain)
            ->path($domain ? '' : 'admin')
            ->spa()
            ->spaUrlExceptions([
                '*/admin/revenue/report/*',
                '*/revenue/report/*',
            ])
            ->breadcrumbs(false)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->brandLogo(asset('logo-ihsan.svg'))
            ->darkModeBrandLogo(asset('logo-ihsan-dark.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.svg'))
            ->login(AdminLogin::class)
            ->profile(EditProfile::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->maxContentWidth('7xl')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->routes(function () {
                Route::get('/revenue/report/all/{format}/{period?}', [RevenueReportController::class, 'downloadAll'])
                    ->whereIn('format', ['csv', 'pdf'])
                    ->middleware([Authenticate::class])
                    ->name('pages.revenue.report.all');

                Route::get('/revenue/report/{organizationPublicId}/{format}/{period?}', [RevenueReportController::class, 'download'])
                    ->whereIn('format', ['csv', 'pdf'])
                    ->middleware([Authenticate::class])
                    ->name('pages.revenue.report');

                Route::get('/', fn () => redirect()->route('filament.admin.pages.platform-overview'))->name('pages.dashboard');
            })
            ->pages([
                PlatformOverview::class,
                Transactions::class,
                ProcessingFees::class,
                MonthlyInvoices::class,
                Revenue::class,
                FraudPrevention::class,
                SendNotificationToOrgs::class,
            ])
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                DonationTrendChart::class,
                ProcessingFeeTrendChart::class,
                TopOrganizationsChart::class,
                PaymentMethodChart::class,
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
            ]);
    }
}
