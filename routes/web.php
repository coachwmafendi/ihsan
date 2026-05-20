<?php

use App\Http\Controllers\StripePaymentIntentController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\DonationForm;
use App\Models\Element as ElementModel;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('/donate/{element:token}', DonationForm::class)->name('donations.show');
Route::get('/checkout/{form}', function (string $form) {
    $element = ElementModel::query()
        ->where('is_active', true)
        ->whereHas('campaign', fn ($q) => $q->where('form_parameter', $form))
        ->first();

    if (! $element) {
        abort(404);
    }

    return redirect()->route('donations.show', ['element' => $element->token, 'embed' => 1]);
})->name('checkout.form');

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
    Route::get('/', fn () => redirect()->route('donorportal.login'));
    Route::get('login', [DonorAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [DonorAuthController::class, 'sendMagicLink'])
        ->middleware('throttle:5,60')
        ->name('send-magic-link');
    Route::get('login/{token}', [DonorAuthController::class, 'login'])
        ->middleware('throttle:10,60')
        ->name('magic-login');
    Route::post('logout', [DonorAuthController::class, 'logout'])->name('logout');
    Route::get('donations', [DonorPortalController::class, 'donations'])->name('donations');
    Route::get('subscriptions', [DonorPortalController::class, 'subscriptions'])->name('subscriptions');
    Route::post('subscriptions/{subscription}/cancel', [DonorPortalController::class, 'cancelSubscription'])->name('subscriptions.cancel');
});

require __DIR__.'/settings.php';
