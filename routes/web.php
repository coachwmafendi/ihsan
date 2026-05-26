<?php

use App\Http\Controllers\DonationCampaignImageController;
use App\Http\Controllers\EmbedCheckoutController;
use App\Http\Controllers\PublicElementController;
use App\Http\Controllers\ReceiptDownloadController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripePaymentIntentController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\DonationForm;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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
Route::get('/embed.js', [EmbedCheckoutController::class, 'script'])->name('embed.script');
Route::get('/checkout/{form}', [EmbedCheckoutController::class, 'checkout'])->name('checkout.form');

Route::get('/api/public/elements/{token}', [PublicElementController::class, 'show'])
    ->name('api.public.elements.show');

Route::post('/stripe/payment-intent', StripePaymentIntentController::class)
    ->middleware('throttle:10,1')
    ->name('stripe.payment-intent');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/stripe/connect/redirect', [StripeConnectController::class, 'redirect'])
        ->name('stripe.connect.redirect');

    Route::get('/donations/{donation}/receipt', ReceiptDownloadController::class)
        ->name('donations.receipt.download');
});

Route::get('/stripe/connect/callback', [StripeConnectController::class, 'callback'])
    ->name('stripe.connect.callback');

use App\Http\Controllers\DonorAuthController;
use App\Http\Controllers\DonorPortalController;

// Donor portal
Route::prefix('donorportal')->name('donorportal.')->group(function () {
    Route::get('/', fn () => redirect()->route('donorportal.dashboard'));
    Route::get('login', [DonorAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [DonorAuthController::class, 'sendMagicLink'])
        ->middleware('throttle:donor-magic-link')
        ->name('send-magic-link');
    Route::get('login/{token}', [DonorAuthController::class, 'login'])
        ->middleware('throttle:10,60')
        ->name('magic-login');
    Route::post('logout', [DonorAuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', [DonorPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('donations', [DonorPortalController::class, 'donations'])->name('donations');
    Route::get('donations/{donation}/receipt', ReceiptDownloadController::class)
        ->name('donations.receipt.download');
    Route::get('subscriptions', [DonorPortalController::class, 'subscriptions'])->name('subscriptions');
    Route::post('subscriptions/{subscription}/cancel', [DonorPortalController::class, 'cancelSubscription'])->name('subscriptions.cancel');
});
