<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Jobs\SendSubscriptionAmountChangedNotification;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;

class DonorSubscriptionController extends Controller
{
    use DonorPortalScoping;

    private function authorizeSubscriptionAction(Organization $organization, Subscription $subscription, Donor $donor): void
    {
        abort_unless(
            $subscription->donor_id === $donor->getKey()
            && $subscription->campaign?->organization_id === $organization->getKey(),
            403
        );
    }

    private function handleSubscriptionAction(
        Organization $organization,
        Subscription $subscription,
        callable $action,
        string $successMessage,
        string $errorMessage,
    ): RedirectResponse {
        try {
            $action();
        } catch (\Exception $e) {
            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('error', $errorMessage);
        }

        return redirect()->route('donorportal.subscriptions', $organization)
            ->with('success', $successMessage);
    }

    public function subscriptions(Organization $organization)
    {
        $donor = request()->donor;

        return view('donor.subscriptions', [
            'donor' => $donor,
            'organization' => $organization,
            'subscriptions' => $this->scopeToOrg($donor->subscriptions(), $organization)
                ->with('campaign.organization')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function cancel(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        return $this->handleSubscriptionAction(
            $organization,
            $subscription,
            fn () => app(ManageStripeSubscription::class)->cancel($subscription, false),
            'Subscription will cancel at the end of the billing period.',
            'Unable to cancel subscription. Please try again later.',
        );
    }

    public function pause(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        return $this->handleSubscriptionAction(
            $organization,
            $subscription,
            fn () => app(ManageStripeSubscription::class)->pause($subscription),
            'Subscription paused.',
            'Unable to pause subscription. Please try again later.',
        );
    }

    public function resume(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        return $this->handleSubscriptionAction(
            $organization,
            $subscription,
            fn () => app(ManageStripeSubscription::class)->resume($subscription),
            'Subscription resumed.',
            'Unable to resume subscription. Please try again later.',
        );
    }

    public function changeAmount(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        $data = request()->validate([
            'new_amount' => 'required|numeric|min:1|max:99999.99',
        ]);

        $isJson = request()->wantsJson()
            || request()->header('X-Requested-With') === 'XMLHttpRequest'
            || request()->boolean('ajax');

        $previousAmount = (float) $subscription->amount;

        try {
            app(ManageStripeSubscription::class)->changeAmount(
                $subscription,
                (float) $data['new_amount'],
            );
        } catch (\Exception $e) {
            if ($isJson) {
                return response()->json([
                    'error' => 'Unable to update subscription amount. Please try again later.',
                ], 500);
            }

            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('error', 'Unable to update subscription amount. Please try again later.');
        }

        dispatch(new SendSubscriptionAmountChangedNotification($subscription, $previousAmount));

        if ($isJson) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription amount updated.',
                'new_amount' => (float) $data['new_amount'],
            ]);
        }

        return redirect()->route('donorportal.subscriptions', $organization)
            ->with('success', 'Subscription amount updated.');
    }

    public function showIncrease(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        $currentAmount = (float) $subscription->amount;
        $currency = $subscription->currency;
        $symbol = $subscription->currency_symbol;
        $interval = $subscription->interval->value;

        $presetOptions = [
            ['increment' => 5, 'label' => '+ '.$symbol.'5'],
            ['increment' => 80, 'label' => '+ '.$symbol.'80'],
            ['increment' => 100, 'label' => '+ '.$symbol.'100'],
        ];

        return view('donor.subscription-increase', [
            'donor' => request()->donor,
            'organization' => $organization,
            'subscription' => $subscription,
            'currentAmount' => $currentAmount,
            'currency' => $currency,
            'symbol' => $symbol,
            'interval' => $interval,
            'presetOptions' => $presetOptions,
        ]);
    }

    public function paymentMethodClientSecret(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        try {
            $clientSecret = app(ManageStripeSubscription::class)->createSetupIntent($subscription);

            $org = $subscription->campaign?->organization;

            return response()->json([
                'client_secret' => $clientSecret,
                'stripe_account_id' => $org?->stripe_account_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to process payment method update.'], 500);
        }
    }

    public function updatePaymentMethod(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        $data = request()->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            app(ManageStripeSubscription::class)->updatePaymentMethod(
                $subscription,
                $data['payment_method_id'],
            );

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to update payment method.'], 500);
        }
    }
}
