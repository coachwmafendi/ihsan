<?php

namespace App\Http\Controllers;

use App\Mail\MagicLink;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

        if ($donor !== null) {
            $token = $donor->generateMagicToken();

            Mail::to($donor->email)->queue(new MagicLink($donor, $token, $organization));
        }

        return redirect()->route('donorportal.login', $organization)
            ->with('success', 'If that email is registered, a login link has been sent.');
    }

    public function login(Organization $organization, string $token)
    {
        $donor = Donor::query()
            ->where('magic_token', $token)
            ->where('magic_token_expires_at', '>', now())
            ->first();

        if ($donor === null) {
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
