<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $organization = $user->organization;

            if ($organization === null) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')
                    ->with('error', 'Organisasi anda telah dipadam. Sila hubungi admin.');
            }

            if ($organization->status === OrganizationStatus::Suspended) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')
                    ->with('error', 'Akaun organisasi anda telah digantung. Sila hubungi admin.');
            }
        }

        return $next($request);
    }
}
