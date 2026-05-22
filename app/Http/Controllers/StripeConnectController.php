<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\Account;
use Stripe\OAuth;
use Stripe\Stripe;

class StripeConnectController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $org = auth()->user()?->organization;

        if ($org === null) {
            return redirect()->route('filament.app.pages.stripe-onboarding');
        }

        $clientId = config('services.stripe.connect_client_id');

        if (! $clientId) {
            return redirect()->route('filament.app.pages.stripe-onboarding')
                ->with('error', 'Ciri Connect Stripe belum dikonfigurasi.');
        }

        $authorizeUrl = OAuth::authorizeUrl([
            'client_id' => $clientId,
            'scope' => 'read_write',
            'state' => $org->getKey(),
            'redirect_uri' => route('stripe.connect.callback'),
        ]);

        return redirect($authorizeUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        if ($error) {
            return redirect()->route('filament.app.pages.stripe-onboarding')
                ->with('error', 'Penyambungan Stripe dibatalkan.');
        }

        if (! $code || ! $state) {
            return redirect()->route('filament.app.pages.stripe-onboarding')
                ->with('error', 'Parameter tidak lengkap.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $response = OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('filament.app.pages.stripe-onboarding')
                ->with('error', 'Gagal menukar kod OAuth: '.$e->getMessage());
        }

        $stripeUserId = $response->stripe_user_id;

        if (! $stripeUserId) {
            return redirect()->route('filament.app.pages.stripe-onboarding')
                ->with('error', 'Tiada stripe_user_id dalam respons OAuth.');
        }

        $org = Organization::query()->find($state);

        if ($org === null) {
            return redirect()->route('filament.app.pages.stripe-onboarding')
                ->with('error', 'Organisasi tidak dijumpai.');
        }

        $org->update([
            'stripe_account_id' => $stripeUserId,
            'stripe_onboarded' => true,
            'stripe_onboarded_at' => now(),
        ]);

        try {
            $account = Account::retrieve($stripeUserId);

            $stripeName = $account->business_profile->name
                ?? $account->settings->dashboard->display_name
                ?? null;

            if ($stripeName) {
                $org->update(['name' => $stripeName]);
            }
        } catch (\Throwable) {
            // not critical
        }

        $notification = Notification::make()
            ->title('Akaun Stripe berjaya disambung')
            ->success()
            ->toArray();

        session()->flash('filament.notifications', [$notification]);

        return redirect()->route('filament.app.pages.insights');
    }
}
