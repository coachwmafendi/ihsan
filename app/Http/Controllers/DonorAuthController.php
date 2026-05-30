<?php

namespace App\Http\Controllers;

use App\Mail\MagicLink;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class DonorAuthController extends Controller
{
    public function showLoginForm(Organization $organization)
    {
        return view('donor.login', ['organization' => $organization]);
    }

    public function sendMagicLink(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $donor = Donor::query()->where('email', $validated['email'])->first();

        RateLimiter::hit('donor-magic-link-email:'.$validated['email']);

        if ($donor !== null) {
            $token = $donor->generateMagicToken();

            Mail::to($donor->email)->queue(new MagicLink($donor, $token, $organization));
        }

        usleep(random_int(100000, 200000));

        return redirect()->route('donorportal.login', $organization)
            ->with('success', 'If that email is registered, a login link has been sent.');
    }

    public function login(Organization $organization, string $token)
    {
        $donor = Donor::query()
            ->where('magic_token', hash('sha256', $token))
            ->first();

        if ($donor === null || ! $donor->isValidMagicToken($token)) {
            return redirect()->route('donorportal.login', $organization)->with('error', 'Invalid or expired login link.');
        }

        session()->put('donor_id', $donor->getKey());
        session()->put('organization_id', $organization->getKey());

        return redirect()->route('donorportal.dashboard', $organization);
    }

    public function logout(Organization $organization)
    {
        session()->forget(['donor_id', 'organization_id']);

        return redirect()->route('donorportal.login', $organization);
    }
}
