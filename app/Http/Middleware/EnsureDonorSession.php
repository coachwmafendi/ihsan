<?php

namespace App\Http\Middleware;

use App\Models\Donor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDonorSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        $donorId = session('donor_id');
        $orgId = session('organization_id');

        if ($donorId === null || (string) $orgId !== (string) $organization->getKey()) {
            return $request->expectsJson()
                ? abort(403)
                : redirect()->route('donorportal.login', $organization);
        }

        $donor = Donor::query()->find($donorId);

        if ($donor === null) {
            session()->forget(['donor_id', 'organization_id']);

            return $request->expectsJson()
                ? abort(403)
                : redirect()->route('donorportal.login', $organization);
        }

        $request->merge(['donor' => $donor]);

        return $next($request);
    }
}
