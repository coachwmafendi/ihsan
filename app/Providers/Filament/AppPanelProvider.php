<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\StripeOnboarding;
use App\Filament\Pages\Auth\EditProfile;
use App\Http\Middleware\EnsureOrganizationIsActive;
use App\Http\Middleware\RedirectIfStripeNotOnboarded;
use Filament\Enums\GlobalSearchPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): HtmlString => new HtmlString('<script src="https://js.stripe.com/v3/"></script>'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): HtmlString => new HtmlString(<<<'HTML'
                <script>
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('paymentDetailsModal', (clientSecret, stripeAccount, initial) => ({
                            paymentMethod: 'current',
                            clientSecret: clientSecret,
                            stripeAccount: stripeAccount,
                            interval: initial.interval,
                            billingDay: initial.billingDay,
                            cancelAt: initial.cancelAt,
                            unlimitedEndDate: initial.unlimitedEndDate,
                            coverFee: initial.coverFee,
                            amount: initial.amount,
                            processingFeeRate: initial.processingFeeRate,
                            cardError: '',
                            loading: false,
                            success: false,
                            stripe: null,
                            elements: null,

                            init() {
                                this.$watch('paymentMethod', value => {
                                    if (value === 'new' && this.clientSecret) {
                                        this.$nextTick(() => this.mountCard());
                                    }
                                });
                            },

                            mountCard() {
                                const container = document.getElementById('stripe-card-element');
                                if (!container || container.children.length > 0) return;

                                const stripeOptions = this.stripeAccount ? { stripeAccount: this.stripeAccount } : {};
                                this.stripe = Stripe(this.$el.dataset.stripeKey, stripeOptions);
                                this.elements = this.stripe.elements({
                                    clientSecret: this.clientSecret,
                                    appearance: { theme: 'stripe' },
                                });

                                const card = this.elements.create('payment');
                                card.mount('#stripe-card-element');
                                card.on('change', e => { this.cardError = e.error ? e.error.message : ''; });
                            },

                            async save() {
                                this.loading = true;
                                this.cardError = '';

                                // Always save subscription details first
                                await this.$wire.saveDetails(
                                    this.amount,
                                    this.interval,
                                    this.billingDay,
                                    this.cancelAt || null,
                                    this.coverFee,
                                );

                                // If new card selected, confirm Stripe setup
                                if (this.paymentMethod === 'new') {
                                    if (!this.stripe || !this.elements) {
                                        this.loading = false;
                                        return;
                                    }

                                    const { setupIntent, error } = await this.stripe.confirmSetup({
                                        elements: this.elements,
                                        redirect: 'if_required',
                                    });

                                    if (error) {
                                        this.cardError = error.message;
                                        this.loading = false;
                                        return;
                                    }

                                    await this.$wire.savePaymentMethod(setupIntent.payment_method);
                                }

                                this.success = true;
                                this.loading = false;
                                setTimeout(() => this.$wire.unmountAction(), 1500);
                            },
                        }));
                    });
                </script>
                HTML),
        );
    }

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
            ->darkMode(false)
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
                EnsureOrganizationIsActive::class,
                RedirectIfStripeNotOnboarded::class,
            ]);
    }
}
