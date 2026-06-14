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

        if ($request->routeIs('app.stripe-onboarding')) {
            return $next($request);
        }

        if (! $user->organization->stripe_onboarded) {
            return redirect()->route('app.stripe-onboarding');
        }

        return $next($request);
    }
}
