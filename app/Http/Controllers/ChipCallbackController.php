<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ChipCallbackController extends Controller
{
    public function __invoke(Request $request, Donation $donation, string $status): View
    {
        $campaign = $donation->campaign;
        $returnTo = $request->query('return_to');

        return view('chip.callback', [
            'donation' => $donation,
            'status' => $status,
            'campaignUrl' => $campaign ? route('campaigns.public', $campaign) : route('home'),
            'returnTo' => filled($returnTo) ? $returnTo : null,
        ]);
    }
}
