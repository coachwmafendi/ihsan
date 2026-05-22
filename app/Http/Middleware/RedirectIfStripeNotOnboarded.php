<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfStripeNotOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->organization === null) {
            return $next($request);
        }

        $org = $user->organization;

        if ($org->stripe_account_id && ! $org->stripe_onboarded) {
            return redirect()->route('filament.app.pages.stripe-onboarding');
        }

        if ($org->stripe_account_id === null) {
            return redirect()->route('filament.app.pages.stripe-onboarding');
        }

        return $next($request);
    }
}
