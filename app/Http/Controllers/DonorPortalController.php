<?php

namespace App\Http\Controllers;

use App\Jobs\SendDonorProblemReport;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;

class DonorPortalController extends Controller
{
    public function reportProblem(Organization $organization): RedirectResponse
    {
        $donor = request()->donor;

        $data = request()->validate([
            'message' => 'required|string|max:5000',
        ]);

        $executed = RateLimiter::attempt(
            'donor-report:'.$donor->getKey(),
            5,
            function () use ($donor, $organization, $data): void {
                dispatch(new SendDonorProblemReport($donor, $organization, $data['message']));
            },
            3600
        );

        if (! $executed) {
            return redirect()->route('donorportal.dashboard', $organization)
                ->with('error', 'You have sent too many messages. Please try again later.');
        }

        return redirect()->route('donorportal.dashboard', $organization)
            ->with('success', 'Thank you for your feedback. We will review it shortly.');
    }
}
