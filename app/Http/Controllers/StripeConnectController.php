<?php

namespace App\Http\Controllers;

use App\Jobs\RegisterStripePaymentMethodDomains;
use App\Models\Organization;
use App\Services\AuditLogLogger;
use App\Services\StripeOAuthClient;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Account;
use Stripe\Stripe;

class StripeConnectController extends Controller
{
    public function __construct(private readonly StripeOAuthClient $stripeOAuth) {}

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

        $authorizeUrl = $this->stripeOAuth->authorizeUrl([
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
                ->with('error', 'Stripe did not return a complete response. Please try connecting again.');
        }

        if ($state !== session('stripe_connect_state')) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Your session does not match the Stripe response. Please start the connection again.');
        }

        $org = Organization::query()->find(session('stripe_connect_org_id'));

        if ($org === null) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Organization not found. Please sign in and try again.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $response = $this->stripeOAuth->token([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe Connect OAuth token exchange failed', [
                'organization_id' => $org->getKey(),
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Stripe could not complete the connection. Please try again or contact support.');
        }

        $stripeUserId = $response->stripe_user_id;

        if (! $stripeUserId) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'Stripe did not return an account identifier. Please try again.');
        }

        $existingOrg = Organization::query()
            ->where('stripe_account_id', $stripeUserId)
            ->whereKeyNot($org->getKey())
            ->first();

        if ($existingOrg !== null) {
            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'This Stripe account is already connected to another Ihsan organization. Please use a different Stripe account or contact support.');
        }

        try {
            $org->update([
                'stripe_account_id' => $stripeUserId,
                'stripe_onboarded' => true,
                'stripe_onboarded_at' => now(),
            ]);

            AuditLogLogger::stripeConnected($org, auth()->user(), $stripeUserId);

            RegisterStripePaymentMethodDomains::dispatch($org->id);
        } catch (QueryException $e) {
            Log::error('Failed to save Stripe Connect account', [
                'organization_id' => $org->getKey(),
                'stripe_account_id' => $stripeUserId,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('app.stripe-onboarding')
                ->with('error', 'We could not save the Stripe connection. Please try again or contact support if the problem persists.');
        }

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

        return redirect()->route('app.settings.payment')
            ->with('stripe_connect_success', 'Stripe account connected successfully.');
    }
}
