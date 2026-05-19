<?php

use App\Http\Controllers\StripePaymentIntentController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\DonationForm;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('/donate/{element:token}', DonationForm::class)->name('donations.show');

Route::post('/stripe/payment-intent', StripePaymentIntentController::class)
    ->middleware('throttle:10,1')
    ->name('stripe.payment-intent');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
