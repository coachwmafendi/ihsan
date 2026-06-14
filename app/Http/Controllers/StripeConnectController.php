<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\AuditLogLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Account;
use Stripe\OAuth;
use Stripe\Stripe;

class StripeConnectController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $org = auth()->user()?->organization;

        if ($org === null) {
            return redirect()->route('app.stripe-onboarding');
        }

        $clientId = config('services.stripe.connect_client_id');

        if (! $clientId) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Stripe Connect feature not configured.');
        }

        $state = Str::random(40);
        session(['stripe_connect_state' => $state]);
        session(['stripe_connect_org_id' => $org->getKey()]);

        $authorizeUrl = OAuth::authorizeUrl([
            'client_id' => $clientId,
            'scope' => 'read_write',
            'state' => $state,
            'redirect_uri' => route('stripe.connect.callback'),
        ]);

        return redirect($authorizeUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        Log::info('Stripe Connect callback', [
            'has_code' => (bool) $code,
            'has_state' => (bool) $state,
            'has_error' => (bool) $error,
            'session_state' => session('stripe_connect_state') ? 'present' : 'missing',
            'session_org_id' => session('stripe_connect_org_id'),
            'auth_user' => auth()->id(),
        ]);

        if ($error) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Stripe connection was cancelled: '.$error);
        }

        if (! $code || ! $state) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Incomplete parameters (code='.($code ? 'yes' : 'no').', state='.($state ? 'yes' : 'no').').');
        }

        if ($state !== session('stripe_connect_state')) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Invalid state parameter. Session state: '.(session('stripe_connect_state') ? 'present but mismatch' : 'missing').'.');
        }

        $org = Organization::query()->find(session('stripe_connect_org_id'));

        if ($org === null) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Organization not found.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $response = OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Failed to connect Stripe Connect account. Please try again.');
        }

        $stripeUserId = $response->stripe_user_id;

        if (! $stripeUserId) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'No stripe_user_id in OAuth response.');
        }

        $org->update([
            'stripe_account_id' => $stripeUserId,
            'stripe_onboarded' => true,
            'stripe_onboarded_at' => now(),
        ]);

        AuditLogLogger::stripeConnected($org, auth()->user(), $stripeUserId);

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

        session()->flash('success', 'Stripe account connected successfully.');

        return redirect()->route('app.dashboard');
    }
}
