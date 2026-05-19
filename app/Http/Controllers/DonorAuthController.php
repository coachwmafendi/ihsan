<?php

namespace App\Http\Controllers;

use App\Models\Donor;

class DonorAuthController extends Controller
{
    public function login(string $token)
    {
        $donor = Donor::query()
            ->where('magic_token', $token)
            ->where('magic_token_expires_at', '>', now())
            ->first();

        if ($donor === null) {
            return redirect()->route('home')->with('error', 'Invalid or expired login link.');
        }

        session()->put('donor_id', $donor->getKey());

        return redirect()->route('donor.donations');
    }

    public function logout()
    {
        session()->forget('donor_id');

        return redirect()->route('home');
    }
}
