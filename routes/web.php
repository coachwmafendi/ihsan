<?php

use App\Http\Controllers\ChipCallbackController;
use App\Http\Controllers\ChipFinalizeController;
use App\Http\Controllers\ChipWebhookController;
use App\Http\Controllers\DemoLandingController;
use App\Http\Controllers\DonationCampaignImageController;
use App\Http\Controllers\DonorImpersonationController;
use App\Http\Controllers\DonorNotificationController;
use App\Http\Controllers\EmbedCheckoutController;
use App\Http\Controllers\PublicElementController;
use App\Http\Controllers\QrRedirectController;
use App\Http\Controllers\ReceiptDownloadController;
use App\Http\Controllers\StripePaymentIntentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Webhooks\EmailWebhookController;
use App\Livewire\Auth\RegisterOrganization;
use App\Livewire\CampaignPublicPage;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::view('/', 'welcome')->name('home');

// Legacy panel URLs: the panel moved to its own subdomain.
Route::get('/app/{path?}', function (string $path = '') {
    $target = 'https://'.config('app.app_panel_domain').app_panel_port_suffix().'/'.$path;

    if ($query = request()->getQueryString()) {
        $target .= '?'.$query;
    }

    return redirect()->away($target, 301);
})->where('path', '.*');

Route::get('/login', fn () => redirect()->away(
    'https://'.config('app.app_panel_domain').app_panel_port_suffix().'/login', 301
));

Route::get('/register-organization', RegisterOrganization::class)->name('register.org');
Route::get('/demo/msk', DemoLandingController::class)->name('demo.msk');
Route::view('/case-studies/madrasah-darul-falah', 'case-studies.madrasah-darul-falah')->name('case-studies.madrasah-darul-falah');

Route::get('/_test-widget', fn () => response('<!DOCTYPE html><html><body style="padding:40px"><h2>Widget Test</h2><script src="/e/widget.js" data-token="sgxqLo" data-api-base="'.url('/').'"></script></body></html>')->header('Content-Type', 'text/html'));

Route::get('/_debug-ip', function () {
    return [
        'laravel_ip' => request()->ip(),
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'x_forwarded_for' => request()->header('X-Forwarded-For'),
        'cf_connecting_ip' => request()->header('CF-Connecting-IP'),
        'x_forwarded_proto' => request()->header('X-Forwarded-Proto'),
        'user_agent' => request()->userAgent(),
        'is_cloudflare_ip' => request()->ip() !== ($_SERVER['REMOTE_ADDR'] ?? null),
    ];
});

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
Route::get('/qr/{element:token}', [QrRedirectController::class, 'show'])->name('qr.redirect');
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

Route::post('/impersonate/exit', [DonorImpersonationController::class, 'exit'])
    ->name('admin.donor-portal.exit');

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
        Route::get('annual-statement', [DonorDonationController::class, 'downloadAnnualStatement'])
            ->name('donations.annual-statement');
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

        $amountDisplay = $subscription->displayAmount();

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
