<?php

use App\Http\Controllers\DonationCampaignImageController;
use App\Http\Controllers\EmbedCheckoutController;
use App\Http\Controllers\PublicElementController;
use App\Http\Controllers\ReceiptDownloadController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripePaymentIntentController;
use App\Http\Controllers\StripeWebhookController;
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
use App\Livewire\App\Insights;
use App\Livewire\App\Notifications\Index as NotificationsIndex;
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
use App\Livewire\Auth\RegisterOrganization;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/register-organization', RegisterOrganization::class)->name('register.org');

Route::get('/_test-widget', fn () => response('<!DOCTYPE html><html><body style="padding:40px"><h2>Widget Test</h2><script src="/e/widget.js" data-token="sgxqLo" data-api-base="'.config('app.url').'"></script></body></html>')->header('Content-Type', 'text/html'));

Route::get('/language/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ms'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    return redirect()->back();
})->name('language.switch');
Route::get('/donate/{element:token}/image', DonationCampaignImageController::class)->name('donations.campaign-image');
Route::get('/donate/campaign/{campaign:form_parameter}/image', [DonationCampaignImageController::class, 'campaignImage'])->name('donations.campaign-image-campaign');
Route::livewire('/donate/{element:token}', DonationForm::class)->name('donations.show');
Route::livewire('/donate/campaign/{campaign:form_parameter}', DonationForm::class)->name('donations.campaign-show');
Route::get('/e/widget.js', [EmbedCheckoutController::class, 'widget'])->name('widget.script');
Route::get('/e/loader.js', [EmbedCheckoutController::class, 'loader'])->name('loader.script');
Route::get('/embed.js', [EmbedCheckoutController::class, 'script'])->name('embed.script');
Route::get('/checkout/{form}', [EmbedCheckoutController::class, 'checkout'])->name('checkout.form');

Route::get('/api/public/elements/{token}', [PublicElementController::class, 'show'])
    ->name('api.public.elements.show');

Route::post('/stripe/payment-intent', StripePaymentIntentController::class)
    ->middleware('throttle:10,1')
    ->name('stripe.payment-intent');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware(['auth', EnsureNgoAdmin::class, RedirectIfStripeNotOnboarded::class])->group(function () {
    Route::get('/app', fn () => redirect()->route('app.insights'))->name('app');
    Route::get('/app/dashboard', Dashboard::class)->name('app.dashboard');
    Route::get('/app/insights', Insights::class)->name('app.insights');

    Route::get('/app/campaigns', CampaignIndex::class)->name('app.campaigns.index');
    Route::get('/app/campaigns/create', CampaignCreate::class)->name('app.campaigns.create');
    Route::get('/app/campaigns/{campaign:public_id}/edit', CampaignEdit::class)->name('app.campaigns.edit');

    Route::get('/app/donations', DonationIndex::class)->name('app.donations.index');
    Route::get('/app/donations/{donation:public_id}', DonationShow::class)->name('app.donations.show');

    Route::get('/app/supporters', SupporterIndex::class)->name('app.supporters.index');
    Route::get('/app/supporters/{donor:public_id}', SupporterShow::class)->name('app.supporters.show');

    Route::get('/app/settings/organization', OrganizationSettings::class)->name('app.settings.organization');
    Route::get('/app/settings/payment', Payment::class)->name('app.settings.payment');
    Route::get('/app/settings/notifications', Notifications::class)->name('app.settings.notifications');
    Route::get('/app/settings/tracking', Tracking::class)->name('app.settings.tracking');
    Route::get('/app/settings/installation', Installation::class)->name('app.settings.installation');
    Route::get('/app/settings/allow-domains', AllowDomains::class)->name('app.settings.allow-domains');
    Route::get('/app/settings/donor-portal', DonorPortal::class)->name('app.settings.donor-portal');
    Route::get('/app/settings/account', Account::class)->name('app.settings.account');
    Route::get('/app/notifications', NotificationsIndex::class)->name('app.notifications.index');
    Route::get('/app/billing', Billing::class)->name('app.billing');
    Route::get('/app/stripe-onboarding', StripeOnboarding::class)->name('app.stripe-onboarding');

    Route::get('/app/subscriptions', SubscriptionIndex::class)->name('app.subscriptions.index');
    Route::get('/app/subscriptions/{subscription:public_id}', SubscriptionShow::class)->name('app.subscriptions.show');
    Route::get('/app/recurring-plans', SubscriptionIndex::class)->name('app.recurring-plans');

    Route::get('/app/elements', ElementIndex::class)->name('app.elements.index');
    Route::get('/app/elements/create', ElementCreate::class)->name('app.elements.create');
    Route::get('/app/elements/{element}/edit', ElementEdit::class)->name('app.elements.edit');
    Route::get('/app/virtual-terminal', VirtualTerminal::class)->name('app.virtual-terminal');
    Route::get('/app/audit-log', AuditLogIndex::class)->name('app.audit-log.index');

    // Placeholder routes for upcoming features
    Route::get('/app/payouts', fn () => redirect('/app/dashboard'))->name('app.payouts');
    Route::get('/app/members', fn () => redirect('/app/dashboard'))->name('app.members');
    Route::get('/app/teams', fn () => redirect('/app/dashboard'))->name('app.teams');
    Route::get('/app/developer/api-keys', fn () => redirect('/app/dashboard'))->name('app.developer.api-keys');
    Route::get('/app/developer/webhooks', fn () => redirect('/app/dashboard'))->name('app.developer.webhooks');
    Route::get('/app/developer/embed-forms', fn () => redirect('/app/dashboard'))->name('app.developer.embed-forms');

    Route::get('/stripe/connect/redirect', [StripeConnectController::class, 'redirect'])
        ->name('stripe.connect.redirect');

    Route::get('/donations/{donation}/receipt', ReceiptDownloadController::class)
        ->name('donations.receipt.download');
});

Route::get('/stripe/connect/callback', [StripeConnectController::class, 'callback'])
    ->name('stripe.connect.callback');

use App\Http\Controllers\DonorAuthController;
use App\Http\Controllers\DonorDashboardController;
use App\Http\Controllers\DonorDonationController;
use App\Http\Controllers\DonorPortalController;
use App\Http\Controllers\DonorProfileController;
use App\Http\Controllers\DonorSubscriptionController;
use App\Models\Donor;

// Organization logo (served from private storage)
Route::get('/org/{organization}/logo', function (App\Models\Organization $organization): ?StreamedResponse {
    if (! filled($organization->logo_path)) {
        abort(404);
    }

    $disk = Storage::disk('public');

    if (! $disk->exists($organization->logo_path)) {
        $disk = Storage::disk('local');
    }

    if (! $disk->exists($organization->logo_path)) {
        abort(404);
    }

    return $disk->response($organization->logo_path);
})->name('organization.logo');

// Donor photo (served from private storage)
Route::get('/donor/{donor}/photo', function (Donor $donor): ?StreamedResponse {
    if (! filled($donor->photo_path)) {
        abort(404);
    }

    $isAdmin = auth()->check();
    $isDonor = session('donor_id') === $donor->getKey();

    if (! $isAdmin && ! $isDonor) {
        abort(404);
    }

    $disk = Storage::disk();

    if (! $disk->exists($donor->photo_path)) {
        $disk = Storage::disk('local');
    }

    return $disk->response($donor->photo_path);
})->name('donor.photo');

// Donor portal
Route::prefix('donorportal/{organization:code}')->name('donorportal.')->group(function () {
    Route::get('/', fn (\App\Models\Organization $organization) => redirect()->route('donorportal.dashboard', $organization));
    Route::get('login', [DonorAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [DonorAuthController::class, 'sendMagicLink'])
        ->middleware('throttle:donor-magic-link')
        ->name('send-magic-link');
    Route::get('login/{token}', [DonorAuthController::class, 'login'])
        ->middleware('throttle:10,60')
        ->name('magic-login');
    Route::post('logout', [DonorAuthController::class, 'logout'])->name('logout');

    Route::middleware('donor.auth')->group(function () {
        Route::get('dashboard', [DonorDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('donations', [DonorDonationController::class, 'donations'])->name('donations');
        Route::get('donations/{donation}/receipt', [ReceiptDownloadController::class, 'downloadForOrganization'])
            ->name('donations.receipt.download');
        Route::get('receipts', [DonorDonationController::class, 'downloadAllReceipts'])
            ->name('donations.receipts.download-all');
        Route::get('subscriptions', [DonorSubscriptionController::class, 'subscriptions'])->name('subscriptions');
        Route::post('subscriptions/{subscription}/cancel', [DonorSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('subscriptions/{subscription}/pause', [DonorSubscriptionController::class, 'pause'])->name('subscriptions.pause');
        Route::post('subscriptions/{subscription}/resume', [DonorSubscriptionController::class, 'resume'])->name('subscriptions.resume');
        Route::post('subscriptions/{subscription}/change-amount', [DonorSubscriptionController::class, 'changeAmount'])->name('subscriptions.change-amount');
        Route::get('subscriptions/{subscription}/increase', [DonorSubscriptionController::class, 'showIncrease'])->name('subscriptions.increase');
        Route::get('subscriptions/{subscription}/payment-method/client-secret', [DonorSubscriptionController::class, 'paymentMethodClientSecret'])->name('subscriptions.payment-method.client-secret');
        Route::post('subscriptions/{subscription}/payment-method', [DonorSubscriptionController::class, 'updatePaymentMethod'])->name('subscriptions.payment-method.update');
        Route::get('profile', [DonorProfileController::class, 'profile'])->name('profile');
        Route::post('profile', [DonorProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('report-problem', [DonorPortalController::class, 'reportProblem'])->name('report-problem');
    });
});
