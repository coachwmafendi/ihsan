<?php

namespace App\Http\Controllers;

use App\Mail\MagicLink;
use App\Models\Donor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DonorAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('donor.login');
    }

    public function sendMagicLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $donor = Donor::query()->where('email', $validated['email'])->first();

        if ($donor !== null) {
            $token = $donor->generateMagicToken();

            Mail::to($donor->email)->queue(new MagicLink($donor, $token));
        }

        return redirect()->route('donorportal.login')
            ->with('success', 'If that email is registered, a login link has been sent.');
    }

    public function login(string $token)
    {
        $donor = Donor::query()
            ->where('magic_token', $token)
            ->where('magic_token_expires_at', '>', now())
            ->first();

        if ($donor === null) {
            return redirect()->route('donorportal.login')->with('error', 'Invalid or expired login link.');
        }

        session()->put('donor_id', $donor->getKey());

        return redirect()->route('donorportal.dashboard');
    }

    public function logout()
    {
        session()->forget('donor_id');

        return redirect()->route('donorportal.login');
    }
}
