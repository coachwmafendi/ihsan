<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class DonorPortalController extends Controller
{
    public function reportProblem(Organization $organization): RedirectResponse
    {
        $donor = request()->donor;

        $data = request()->validate([
            'message' => 'required|string|max:5000',
        ]);

        $admins = User::query()
            ->where('organization_id', $organization->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            Mail::raw("A donor has reported a problem:\n\n".$data['message']."\n\n---\nDonor: {$donor->name} ({$donor->email})\nOrganization: {$organization->name}", function ($message) use ($admin, $organization) {
                $message->to($admin->email)
                    ->subject('Report a Problem — '.$organization->name)
                    ->replyTo($admin->email);
            });
        }

        return redirect()->route('donorportal.dashboard', $organization)
            ->with('success', 'Thank you for your feedback. We will review it shortly.');
    }
}
