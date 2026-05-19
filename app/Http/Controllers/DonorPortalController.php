<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Donor;

class DonorPortalController extends Controller
{
    private function getDonor(): ?Donor
    {
        $donorId = session('donor_id');
        if ($donorId === null) {
            return null;
        }

        return Donor::query()->with('donations.campaign.organization', 'subscriptions.campaign')->find($donorId);
    }

    public function donations()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('home');
        }

        return view('donor.donations', [
            'donor' => $donor,
            'donations' => $donor->donations()->latest()->paginate(10),
        ]);
    }

    public function subscriptions()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('home');
        }

        return view('donor.subscriptions', [
            'donor' => $donor,
            'subscriptions' => $donor->subscriptions()->latest()->paginate(10),
        ]);
    }

    public function cancelSubscription(string $id)
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('home');
        }

        $subscription = $donor->subscriptions()->findOrFail($id);
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('donor.subscriptions')->with('success', 'Subscription cancelled.');
    }
}
