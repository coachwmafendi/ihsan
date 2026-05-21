<?php

use App\Http\Controllers\EmbedCheckoutController;
use App\Http\Controllers\StripePaymentIntentController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\DonationForm;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('/donate/{element:token}', DonationForm::class)->name('donations.show');
Route::get('/embed.js', [EmbedCheckoutController::class, 'script'])->name('embed.script');
Route::get('/checkout/{form}', [EmbedCheckoutController::class, 'checkout'])->name('checkout.form');

Route::post('/stripe/payment-intent', StripePaymentIntentController::class)
    ->middleware('throttle:10,1')
    ->name('stripe.payment-intent');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

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
    Route::get('subscriptions', [DonorPortalController::class, 'subscriptions'])->name('subscriptions');
    Route::post('subscriptions/{subscription}/cancel', [DonorPortalController::class, 'cancelSubscription'])->name('subscriptions.cancel');
});

require __DIR__.'/settings.php';
