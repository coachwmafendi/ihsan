<?php

namespace App\Http\Controllers;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\MagicLink;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

        $rateLimitKey = 'donor-magic-link-email:'.$validated['email'];

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return redirect()->route('donorportal.login', $organization)
                ->withInput()
                ->with('error', 'Too many login attempts. Please try again later.');
        }

        RateLimiter::hit($rateLimitKey);

        $donor = Donor::query()->where('email', $validated['email'])->first();

        if ($donor !== null && $donor->canReceiveEmails()) {
            $token = $donor->generateMagicToken();
            $messageId = Str::uuid()->toString();
            $mailable = new MagicLink($donor, $token, $organization, $messageId);

            app(LogDonorEmail::class)->handle(
                donor: $donor,
                mailable: $mailable,
                organization: $organization,
                messageId: $messageId,
            );

            Mail::to($donor->email)->queue($mailable);
        }

        usleep(random_int(100000, 200000));

        return redirect()->route('donorportal.login', $organization)
            ->with('success', 'If that email is registered, a login link has been sent.');
    }

    public function login(Request $request, Organization $organization, string $token)
    {
        $donor = Donor::query()
            ->where('magic_token', hash('sha256', $token))
            ->first();

        if ($donor === null || ! $donor->isValidMagicToken($token)) {
            return redirect()->route('donorportal.login', $organization)->with('error', 'Invalid or expired login link.');
        }

        $request->session()->put('donor_id', $donor->getKey());
        $request->session()->put('organization_id', $organization->getKey());
        $request->session()->regenerate();

        /**
         * Expired or tampered impersonation links intentionally fall back to a
         * plain donor login: the donor gets a normal session and no admin
         * impersonation context is attached.
         */
        if ($request->boolean('impersonate') && $request->hasValidSignature()) {
            $request->session()->put([
                'admin_impersonating_donor_id' => $donor->getKey(),
                'admin_impersonating_donor_public_id' => $donor->public_id,
                'admin_impersonating_donor_name' => $donor->name,
                'admin_impersonate_return_url' => $request->query('return', appPanelRoute('app.supporters.index')),
            ]);
        }

        $redirect = $request->query('redirect');
        if ($redirect && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect);
        }

        return redirect()->route('donorportal.dashboard', $organization);
    }

    public function logout(Organization $organization)
    {
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('donorportal.login', $organization);
    }
}
