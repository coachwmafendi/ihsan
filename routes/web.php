<?php

use App\Http\Controllers\ChipCallbackController;
use App\Http\Controllers\ChipFinalizeController;
use App\Http\Controllers\ChipWebhookController;
use App\Http\Controllers\DemoLandingController;
use App\Http\Controllers\DonationCampaignImageController;
use App\Http\Controllers\DonationExportController;
use App\Http\Controllers\DonorImpersonationController;
use App\Http\Controllers\DonorNotificationController;
use App\Http\Controllers\EmailLogResponsivePreviewController;
use App\Http\Controllers\EmbedCheckoutController;
use App\Http\Controllers\PublicElementController;
use App\Http\Controllers\ReceiptDownloadController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripePaymentIntentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionExportController;
use App\Http\Controllers\SupporterExportController;
use App\Http\Controllers\Webhooks\EmailWebhookController;
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
use App\Livewire\CampaignPublicPage;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::view('/', 'welcome')->name('home');
Route::get('/register-organization', RegisterOrganization::class)->name('register.org');
Route::get('/demo/msk', DemoLandingController::class)->name('demo.msk');
Route::view('/case-studies/madrasah-darul-falah', 'case-studies.madrasah-darul-falah')->name('case-studies.madrasah-darul-falah');

Route::get('/_test-widget', fn () => response('<!DOCTYPE html><html><body style="padding:40px"><h2>Widget Test</h2><script src="/e/widget.js" data-token="sgxqLo" data-api-base="'.url('/').'"></script></body></html>')->header('Content-Type', 'text/html'));

Route::get('/_test-iframe-button/{element:token}', function (Element $element) {
    $token = $element->token;
    $baseUrl = url('/');
    $iframe = '<iframe src="'.$baseUrl.'/e/button/'.$token.'" width="100%" height="70" frameborder="0" scrolling="no" style="border:0;overflow:hidden;"></iframe>';
    $listener = <<<'JS'
<script>
(function () {
  if (window.__ihsanModalInstalled) return;
  window.__ihsanModalInstalled = true;
  function openModal(url) {
    var existing = document.getElementById('ihsan-modal');
    if (existing) existing.remove();
    var modal = document.createElement('div');
    modal.id = 'ihsan-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.style.cssText = 'position:fixed;inset:0;z-index:2147483647;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.58);padding:20px;';
    modal.innerHTML = '<div style="position:relative;width:min(100%,520px);height:min(94vh,820px);background:#fff;border-radius:18px;box-shadow:0 24px 80px rgba(15,23,42,.28);overflow:hidden;"><button type="button" data-ihsan-close style="position:absolute;top:10px;right:10px;z-index:2;width:34px;height:34px;border:0;border-radius:999px;background:rgba(15,23,42,.08);font:24px/1 system-ui,sans-serif;cursor:pointer;">&times;</button><iframe title="Ihsan checkout" data-ihsan-frame src="' + url + '" style="width:100%;height:100%;border:0;"></iframe></div>';
    modal.addEventListener('click', function (event) { if (event.target === modal || event.target.closest('[data-ihsan-close]')) closeModal(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeModal(); });
    document.body.appendChild(modal);
    document.documentElement.style.overflow = 'hidden';
  }
  function closeModal() {
    var modal = document.getElementById('ihsan-modal');
    if (!modal) return;
    modal.remove();
    document.documentElement.style.overflow = '';
  }
  window.addEventListener('message', function (event) {
    if (!event.data || typeof event.data !== 'object') return;
    if (event.data.type === 'ihsan:open-checkout') { openModal(event.data.url); if (event.source) { event.source.postMessage({ type: 'ihsan:checkout-ack' }, '*'); } }
    if (event.data.type === 'donation-popup-close') closeModal();
    if (event.data.type === 'ihsan:donation-success') { closeModal(); setTimeout(function () { window.location.reload(); }, 1200); }
  });
})();
</script>
JS;

    return response('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Iframe Button Test</title></head><body style="padding:80px; font-family:sans-serif;"><h3>This is a fake WordPress page</h3>'.$iframe.'\n'.$listener.'</body></html>')->header('Content-Type', 'text/html');
})->name('test.iframe-button');

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
Route::livewire('/campaigns/{campaign:public_id}', CampaignPublicPage::class)->name('campaigns.public');
Route::get('/e/widget.js', [EmbedCheckoutController::class, 'widget'])->name('widget.script');
Route::get('/e/loader.js', [EmbedCheckoutController::class, 'loader'])->name('loader.script');
Route::get('/e/button/{token}', [EmbedCheckoutController::class, 'button'])->name('embed.button');
Route::get('/embed.js', [EmbedCheckoutController::class, 'script'])->name('embed.script');
Route::get('/checkout/{form}', [EmbedCheckoutController::class, 'checkout'])->name('checkout.form');

Route::get('/receipts/{donation:public_id}/{token}', [ReceiptDownloadController::class, 'token'])
    ->name('receipts.token');

Route::get('/receipts/{donation:public_id}', [ReceiptDownloadController::class, 'signed'])
    ->name('receipts.signed');

Route::get('/api/public/elements/{token}', [PublicElementController::class, 'show'])
    ->name('api.public.elements.show');

Route::post('/stripe/payment-intent', StripePaymentIntentController::class)
    ->middleware('throttle:10,1')
    ->name('stripe.payment-intent');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::post('/chip/webhook/{organization:public_id?}', ChipWebhookController::class)
    ->name('chip.webhook');
Route::get('/chip/callback/{donation:public_id}/{status}', ChipCallbackController::class)
    ->whereIn('status', ['success', 'failure', 'cancelled'])
    ->name('chip.callback');
Route::post('/chip/finalize/{donation:public_id}', [ChipFinalizeController::class, 'store'])
    ->name('chip.finalize');
Route::post('/webhooks/mailgun', [EmailWebhookController::class, 'mailgun'])->name('webhooks.mailgun');
Route::post('/webhooks/postmark', [EmailWebhookController::class, 'postmark'])->name('webhooks.postmark');
Route::post('/webhooks/ses/{token}', [EmailWebhookController::class, 'ses'])->name('webhooks.ses');

Route::middleware(['auth', EnsureNgoAdmin::class])->group(function () {
    Route::post('/app/supporters/{donor:public_id}/impersonate', [DonorImpersonationController::class, 'impersonate'])
        ->name('admin.donor-portal.impersonate');
    Route::post('/app/impersonate/exit', [DonorImpersonationController::class, 'exit'])
        ->name('admin.donor-portal.exit');
});

Route::middleware(['auth', EnsureNgoAdmin::class, RedirectIfStripeNotOnboarded::class])->group(function () {
    Route::get('/app', fn () => redirect()->route('app.dashboard'))->name('app');
    Route::get('/app/dashboard', Dashboard::class)->name('app.dashboard');
    Route::get('/app/insights', fn () => redirect()->route('app.dashboard', [], 301))->name('app.insights');

    Route::get('/app/campaigns', CampaignIndex::class)->name('app.campaigns.index');
    Route::get('/app/campaigns/create', CampaignCreate::class)->name('app.campaigns.create');
    Route::get('/app/campaigns/{campaign:public_id}/edit', CampaignEdit::class)->name('app.campaigns.edit');

    Route::get('/app/donations', DonationIndex::class)->name('app.donations.index');
    Route::get('/app/donations/export', DonationExportController::class)->name('app.donations.export');
    Route::get('/app/donations/{donation:public_id}', DonationShow::class)->name('app.donations.show');

    Route::get('/app/supporters', SupporterIndex::class)->name('app.supporters.index');
    Route::get('/app/supporters/export', SupporterExportController::class)->name('app.supporters.export');
    Route::get('/app/supporters/{donor:public_id}', SupporterShow::class)->name('app.supporters.show');
    Route::get('/app/supporters/{donor:public_id}/emails/{emailLog:public_id}/responsive-preview', [EmailLogResponsivePreviewController::class, 'show'])
        ->name('app.supporters.emails.responsive-preview');

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
    Route::get('/app/recurring-plans/export', SubscriptionExportController::class)->name('app.recurring-plans.export');

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

use App\Enums\SubscriptionInterval;
use App\Http\Controllers\DonorAuthController;
use App\Http\Controllers\DonorDashboardController;
use App\Http\Controllers\DonorDonationController;
use App\Http\Controllers\DonorPortalController;
use App\Http\Controllers\DonorProfileController;
use App\Http\Controllers\DonorSubscriptionController;
use App\Mail\LoginAlertNotification;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Mail\SupporterSubscriptionAmountChangedNotification;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

// Organization logo (served from private storage)
Route::get('/org/{organization}/logo', function (Organization $organization): ?StreamedResponse {
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

// Donor notification preferences (signed links from emails)
Route::middleware('signed')->group(function () {
    Route::get('/donor/notifications/{donor}', [DonorNotificationController::class, 'edit'])
        ->name('donor.notifications.edit');
    Route::post('/donor/notifications/{donor}', [DonorNotificationController::class, 'update'])
        ->name('donor.notifications.update');
});

// Donor portal
Route::prefix('donorportal/{organization:code}')->name('donorportal.')->group(function () {
    Route::get('/', fn (Organization $organization) => redirect()->route('donorportal.dashboard', $organization));
    Route::get('login', [DonorAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [DonorAuthController::class, 'sendMagicLink'])
        ->middleware('throttle:donor-magic-link')
        ->name('send-magic-link');
    Route::get('login/{token}', [DonorAuthController::class, 'login'])
        ->middleware('throttle:10,60')
        ->name('magic-login');
    Route::post('logout', [DonorAuthController::class, 'logout'])->name('logout');

    Route::middleware('signed')->group(function () {
        Route::get('subscriptions/{subscription}/increase-link', [DonorSubscriptionController::class, 'showIncreaseLink'])->name('subscriptions.increase-link');
        Route::post('subscriptions/{subscription}/change-amount-link', [DonorSubscriptionController::class, 'changeAmountLink'])->name('subscriptions.change-amount-link');
    });

    Route::middleware('donor.auth')->group(function () {
        Route::get('dashboard', [DonorDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('donations', [DonorDonationController::class, 'donations'])->name('donations');
        Route::get('donations/{donation}/detail', [DonorDonationController::class, 'detail'])->name('donations.detail');
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

if (app()->environment('local')) {
    Route::get('/dev/preview/subscription-amount-change', function () {
        $org = new Organization([
            'name' => 'Ihsan Foundation',
            'email' => 'org@example.com',
            'code' => 'ihsan-foundation',
        ]);
        $org->id = 1;

        $campaign = new Campaign([
            'title' => 'Bantuan Banjir',
            'organization_id' => 1,
        ]);
        $campaign->id = 100;
        $campaign->public_id = 'IH123ABC';
        $campaign->setRelation('organization', $org);

        $donor = new Donor([
            'name' => 'Ahmad Abdullah',
            'email' => 'ahmad@example.com',
            'locale' => 'ms',
        ]);
        $donor->id = 999;
        $donor->public_id = 'DR123456';

        $subscription = new Subscription([
            'amount' => 100.00,
            'currency' => 'myr',
            'currency_symbol' => 'RM',
            'interval' => SubscriptionInterval::Monthly,
        ]);
        $subscription->id = 200;
        $subscription->public_id = 'R1234567';
        $subscription->setRelation('campaign', $campaign);
        $subscription->setRelation('donor', $donor);

        $admin = new User([
            'name' => 'Siti Aminah',
            'email' => 'admin@example.com',
        ]);
        $admin->id = 10;

        $amountDisplay = $subscription->currency_symbol.' '.number_format($subscription->amount, 2);

        $supporterHtml = (new SupporterSubscriptionAmountChangedNotification($subscription, 50.00))->render();
        $adminHtml = (new SubscriptionAmountChangedNotification(
            subscription: $subscription,
            previousAmount: 50.00,
            admin: $admin,
        ))->render();

        $combined = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Email Preview — Subscription Amount Updated</title>
    <style>
        body { font-family: sans-serif; background: #f1f5f9; padding: 20px; }
        .email-block { margin-bottom: 40px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #0f172a; }
    </style>
</head>
<body>
    <div class="email-block">
        <h2>1. Email untuk Supporter / Donor</h2>
        {$supporterHtml}
    </div>
    <div class="email-block">
        <h2>2. Email untuk Org Admin</h2>
        {$adminHtml}
    </div>
</body>
</html>
HTML;

        return response($combined);
    });

    Route::get('/dev/preview/login-alert', function () {
        $organization = new Organization([
            'name' => 'Ihsan Foundation',
            'email' => 'org@example.com',
            'code' => 'ihsan-foundation',
        ]);
        $organization->id = 1;

        $html = (new LoginAlertNotification(
            $organization,
            'Kuala Lumpur, Malaysia',
            '192.168.1.1',
            'Chrome on macOS',
            now()->toImmutable(),
        ))->render();

        return response($html);
    });
}
