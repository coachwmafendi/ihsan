<?php

use App\Http\Controllers\DonationExportController;
use App\Http\Controllers\DonorImpersonationController;
use App\Http\Controllers\EmailLogResponsivePreviewController;
use App\Http\Controllers\ReceiptDownloadController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\SubscriptionExportController;
use App\Http\Controllers\SupporterExportController;
use App\Http\Middleware\EnsureNgoAdmin;
use App\Http\Middleware\RedirectIfStripeNotOnboarded;
use App\Livewire\App\AuditLog\Index as AuditLogIndex;
use App\Livewire\App\Billing;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Livewire\App\Campaigns\CampaignIndex;
use App\Livewire\App\Dashboard;
use App\Livewire\App\Donations\DonationIndex;
use App\Livewire\App\Donations\DonationShow;
use App\Livewire\App\Elements\ElementCreate;
use App\Livewire\App\Elements\ElementEdit;
use App\Livewire\App\Elements\ElementIndex;
use App\Livewire\App\Notifications\Index as NotificationsIndex;
use App\Livewire\App\Reports\MonthlyDonations;
use App\Livewire\App\Settings\Account;
use App\Livewire\App\Settings\AllowDomains;
use App\Livewire\App\Settings\DonorPortal;
use App\Livewire\App\Settings\Installation;
use App\Livewire\App\Settings\Notifications;
use App\Livewire\App\Settings\Organization as OrganizationSettings;
use App\Livewire\App\Settings\Payment;
use App\Livewire\App\Settings\Tracking;
use App\Livewire\App\StripeOnboarding;
use App\Livewire\App\Subscriptions\SubscriptionIndex;
use App\Livewire\App\Subscriptions\SubscriptionShow;
use App\Livewire\App\Supporters\SupporterIndex;
use App\Livewire\App\Supporters\SupporterShow;
use App\Livewire\App\VirtualTerminal;
use Illuminate\Support\Facades\Route;

$appPanelDomain = config('app.app_panel_domain');

if (blank($appPanelDomain)) {
    throw new RuntimeException('APP_PANEL_DOMAIN must be set: the NGO admin panel is served on its own subdomain.');
}

Route::domain($appPanelDomain)->group(function () {
    Route::middleware(['auth', EnsureNgoAdmin::class])->group(function () {
        Route::post('/supporters/{donor:public_id}/impersonate', [DonorImpersonationController::class, 'impersonate'])
            ->name('admin.donor-portal.impersonate');
    });

    Route::middleware(['auth', EnsureNgoAdmin::class, RedirectIfStripeNotOnboarded::class])->group(function () {
        Route::get('/', fn () => redirect()->route('app.dashboard'))->name('app');
        Route::get('/dashboard', Dashboard::class)->name('app.dashboard');
        Route::get('/insights', fn () => redirect()->route('app.dashboard', [], 301))->name('app.insights');

        Route::get('/campaigns', CampaignIndex::class)->name('app.campaigns.index');
        Route::get('/campaigns/create', CampaignCreate::class)->name('app.campaigns.create');
        Route::get('/campaigns/{campaign:public_id}/edit', CampaignEdit::class)->name('app.campaigns.edit');

        Route::get('/donations', DonationIndex::class)->name('app.donations.index');
        Route::get('/donations/export', DonationExportController::class)->name('app.donations.export');
        Route::get('/donations/{donation:public_id}', DonationShow::class)->name('app.donations.show');

        Route::get('/supporters', SupporterIndex::class)->name('app.supporters.index');
        Route::get('/supporters/export', SupporterExportController::class)->name('app.supporters.export');
        Route::get('/supporters/{donor:public_id}', SupporterShow::class)->name('app.supporters.show');
        Route::get('/supporters/{donor:public_id}/emails/{emailLog:public_id}/responsive-preview', [EmailLogResponsivePreviewController::class, 'show'])
            ->name('app.supporters.emails.responsive-preview');

        Route::get('/settings/organization', OrganizationSettings::class)->name('app.settings.organization');
        Route::get('/settings/payment', Payment::class)->name('app.settings.payment');
        Route::get('/settings/notifications', Notifications::class)->name('app.settings.notifications');
        Route::get('/settings/tracking', Tracking::class)->name('app.settings.tracking');
        Route::get('/settings/installation', Installation::class)->name('app.settings.installation');
        Route::get('/settings/allow-domains', AllowDomains::class)->name('app.settings.allow-domains');
        Route::get('/settings/donor-portal', DonorPortal::class)->name('app.settings.donor-portal');
        Route::get('/settings/account', Account::class)->name('app.settings.account');
        Route::get('/notifications', NotificationsIndex::class)->name('app.notifications.index');
        Route::get('/billing', Billing::class)->name('app.billing');
        Route::get('/reports/monthly-donations', MonthlyDonations::class)->name('app.reports.monthly-donations');
        Route::get('/reports/monthly-donations/download/{format}', fn (string $format) => redirect()->route('app.reports.monthly-donations'))
            ->name('app.reports.monthly-donations.download')
            ->where('format', 'csv|pdf');
        Route::get('/stripe-onboarding', StripeOnboarding::class)->name('app.stripe-onboarding');

        Route::get('/subscriptions', SubscriptionIndex::class)->name('app.subscriptions.index');
        Route::get('/subscriptions/{subscription:public_id}', SubscriptionShow::class)->name('app.subscriptions.show');
        Route::get('/recurring-plans', SubscriptionIndex::class)->name('app.recurring-plans');
        Route::get('/recurring-plans/export', SubscriptionExportController::class)->name('app.recurring-plans.export');

        Route::get('/elements', ElementIndex::class)->name('app.elements.index');
        Route::get('/elements/create', ElementCreate::class)->name('app.elements.create');
        Route::get('/elements/{element}/edit', ElementEdit::class)->name('app.elements.edit');
        Route::get('/virtual-terminal', VirtualTerminal::class)->name('app.virtual-terminal');
        Route::get('/audit-log', AuditLogIndex::class)->name('app.audit-log.index');

        // Placeholder routes for upcoming features
        Route::get('/payouts', fn () => redirect()->route('app.dashboard'))->name('app.payouts');
        Route::get('/members', fn () => redirect()->route('app.dashboard'))->name('app.members');
        Route::get('/teams', fn () => redirect()->route('app.dashboard'))->name('app.teams');
        Route::get('/developer/api-keys', fn () => redirect()->route('app.dashboard'))->name('app.developer.api-keys');
        Route::get('/developer/webhooks', fn () => redirect()->route('app.dashboard'))->name('app.developer.webhooks');
        Route::get('/developer/embed-forms', fn () => redirect()->route('app.dashboard'))->name('app.developer.embed-forms');

        Route::get('/stripe/connect/redirect', [StripeConnectController::class, 'redirect'])
            ->name('stripe.connect.redirect');

        Route::get('/donations/{donation}/receipt', ReceiptDownloadController::class)
            ->name('donations.receipt.download');
    });

    // Public within the panel domain: Stripe OAuth return must land on the same
    // host as `redirect` because the state check reads the host-scoped session.
    Route::get('/stripe/connect/callback', [StripeConnectController::class, 'callback'])
        ->name('stripe.connect.callback');
});
