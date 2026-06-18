<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class DonorNotificationController extends Controller
{
    public function edit(Request $request, Donor $donor): View
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'This link is invalid or has expired.');
        }

        return view('donor.notifications.edit', [
            'donor' => $donor,
            'isOptedOut' => $donor->hasOptedOutOfEmails(),
            'updateUrl' => URL::signedRoute('donor.notifications.update', ['donor' => $donor]),
        ]);
    }

    public function update(Request $request, Donor $donor): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'This link is invalid or has expired.');
        }

        $optOut = $request->boolean('opt_out');

        $donor->update([
            'email_opt_out_at' => $optOut ? now() : null,
        ]);

        return redirect()->back()->with('status', $optOut
            ? 'You have unsubscribed from these emails.'
            : 'You have resubscribed to these emails.');
    }

    public static function unsubscribeUrl(Donor $donor): string
    {
        return URL::signedRoute('donor.notifications.edit', ['donor' => $donor]);
    }
}
