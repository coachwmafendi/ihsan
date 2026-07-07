<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $request->session()->put([
            'admin_impersonating_donor_id' => $donor->getKey(),
            'admin_impersonating_donor_public_id' => $donor->public_id,
            'admin_impersonating_donor_name' => $donor->name,
            'admin_impersonate_return_url' => $request->headers->get('referer') ?: route('app.supporters.show', $donor),
        ]);

        return redirect()->route('donorportal.magic-login', [
            'organization' => $organization,
            'token' => $token,
        ]);
    }

    public function exit(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || $user->role !== UserRole::NgoAdmin) {
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
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host === request()->getHost()) {
            return $url;
        }

        return route('app.supporters.index');
    }
}
