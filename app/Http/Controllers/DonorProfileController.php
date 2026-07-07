<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\StripeMetadata;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class DonorProfileController extends Controller
{
    use DonorPortalScoping;

    public function profile(Organization $organization)
    {
        $donor = request()->donor;

        $hasActiveSubscriptions = $this->scopeToOrg($donor->subscriptions(), $organization)
            ->where('status', SubscriptionStatus::Active)
            ->exists();

        return view('donor.profile', [
            'donor' => $donor,
            'organization' => $organization,
            'countries' => config('countries'),
            'hasActiveSubscriptions' => $hasActiveSubscriptions,
        ]);
    }

    public function updateProfile(Organization $organization): RedirectResponse
    {
        $donor = request()->donor;

        $data = request()->validate([
            'title' => 'nullable|string|max:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:donors,email,'.$donor->getKey(),
            'phone' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:255',
            'address_postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|size:2',
            'locale' => 'nullable|string|in:en,ms',
            'sync_stripe' => 'nullable|boolean',
        ]);

        $syncStripe = (bool) ($data['sync_stripe'] ?? false);

        if (request()->hasFile('photo')) {
            if ($donor->photo_path !== null) {
                $disk = Storage::disk();

                if (! $disk->delete($donor->photo_path)) {
                    Storage::disk('local')->delete($donor->photo_path);
                }
            }

            $data['photo_path'] = request()->file('photo')->store('donor-photos');
        }

        $donor->update($data);

        if ($syncStripe && $donor->stripe_customer_id) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));

                $stripeOptions = $organization->stripe_account_id
                    ? ['stripe_account' => $organization->stripe_account_id]
                    : [];

                Customer::update($donor->stripe_customer_id, [
                    'name' => $donor->name,
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? '',
                    'address' => [
                        'line1' => $data['address_line1'] ?? '',
                        'line2' => $data['address_line2'] ?? '',
                        'city' => $data['address_city'] ?? '',
                        'state' => $data['address_state'] ?? '',
                        'postal_code' => $data['address_postal_code'] ?? '',
                        'country' => $data['country'] ?? '',
                    ],
                    'preferred_locales' => StripeMetadata::customerLocale($donor) ?? [],
                ], $stripeOptions);

                $donor->subscriptions()
                    ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
                    ->whereHas('campaign', fn ($q) => $q->where('organization_id', $organization->getKey()))
                    ->each(function (Subscription $subscription) use ($donor, $stripeOptions) {
                        if ($subscription->stripe_subscription_id === null) {
                            return;
                        }

                        StripeSubscription::update($subscription->stripe_subscription_id, [
                            'metadata' => StripeMetadata::forDonorUpdate($donor),
                        ], $stripeOptions);
                    });
            } catch (\Exception $e) {
                report($e);
            }
        }

        return redirect()->route('donorportal.profile', $organization)
            ->with('success', 'Profile updated successfully.');
    }
}
