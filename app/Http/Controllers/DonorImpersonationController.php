<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DonorImpersonationController extends Controller
{
    public function impersonate(Request $request, Donor $donor): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || $user->role !== UserRole::NgoAdmin) {
            abort(403);
        }

        $organization = $user->organization;

        if ($organization === null) {
            abort(404);
        }

        $hasOrgDonation = $donor->donations()
            ->whereHas('campaign', fn (Builder $query) => $query->where('organization_id', $organization->getKey()))
            ->exists();

        $hasOrgSubscription = $donor->subscriptions()
            ->whereHas('campaign', fn (Builder $query) => $query->where('organization_id', $organization->getKey()))
            ->exists();

        if (! $hasOrgDonation && ! $hasOrgSubscription) {
            abort(403);
        }

        $token = $donor->generateMagicToken();

        $returnUrl = $request->headers->get('referer') ?: route('app.supporters.show', $donor);

        return redirect()->away(URL::temporarySignedRoute('donorportal.magic-login', now()->addMinutes(5), [
            'organization' => $organization,
            'token' => $token,
            'impersonate' => 1,
            'return' => $returnUrl,
        ]));
    }

    public function exit(Request $request): RedirectResponse
    {
        if (! $request->session()->has('admin_impersonating_donor_id')) {
            abort(403);
        }

        $returnUrl = $request->session()->get('admin_impersonate_return_url', route('app.supporters.index'));

        $request->session()->forget([
            'admin_impersonating_donor_id',
            'admin_impersonating_donor_public_id',
            'admin_impersonating_donor_name',
            'admin_impersonate_return_url',
            'donor_id',
            'organization_id',
        ]);

        return redirect()->away($this->safeInternalUrl($returnUrl));
    }

    private function safeInternalUrl(string $url): string
    {
        $parts = parse_url($url);

        $isCleanRelative = $parts !== false
            && ! isset($parts['scheme'], $parts['host'])
            && str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && ! str_contains($url, '\\');

        if ($isCleanRelative) {
            return $url;
        }

        $panelHost = config('app.app_panel_domain');

        if ($panelHost && $parts !== false && ($parts['host'] ?? null) === $panelHost) {
            return $url;
        }

        return route('app.supporters.index');
    }
}
