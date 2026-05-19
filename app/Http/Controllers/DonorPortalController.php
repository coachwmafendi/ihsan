<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
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

        return Donor::query()->find($donorId);
    }

    public function donations()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        return view('donor.donations', [
            'donor' => $donor,
            'totalGiven' => $donor->donations()->where('status', DonationStatus::Succeeded)->sum('gross_amount'),
            'donationCount' => $donor->donations()->where('status', DonationStatus::Succeeded)->count(),
            'donations' => $donor->donations()->with('campaign.organization')->latest()->paginate(10),
        ]);
    }

    public function subscriptions()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        return view('donor.subscriptions', [
            'donor' => $donor,
            'subscriptions' => $donor->subscriptions()->with('campaign.organization')->latest()->paginate(10),
        ]);
    }

    public function cancelSubscription(string $id)
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('donorportal.login');
        }

        $subscription = $donor->subscriptions()->findOrFail($id);
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('donorportal.subscriptions')->with('success', 'Subscription cancelled.');
    }
}
