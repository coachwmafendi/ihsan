<?php

namespace App\Http\Controllers;

use App\Actions\Chip\CancelRecurringToken;
use App\Actions\Stripe\CancelLocalRecurringPlan;
use App\Actions\Stripe\ChangeRecurringAmount;
use App\Actions\Stripe\ManageStripeSubscription;
use App\Actions\Stripe\PauseLocalRecurringPlan;
use App\Actions\Stripe\ResumeLocalRecurringPlan;
use App\Actions\Stripe\UpdateAppControlledPaymentMethod;
use App\Jobs\SendSubscriptionAmountChangedNotification;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Stripe\SetupIntent;
use Stripe\Stripe;

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

    private function isStripeBacked(Subscription $subscription): bool
    {
        return filled($subscription->stripe_subscription_id);
    }

    private function isChipBacked(Subscription $subscription): bool
    {
        return filled($subscription->chip_recurring_token);
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

        $successMessage = match (true) {
            $this->isStripeBacked($subscription) => 'Subscription will cancel at the end of the billing period.',
            filled($subscription->chip_recurring_token) => 'Subscription cancelled.',
            default => 'Subscription cancelled.',
        };

        return $this->handleSubscriptionAction(
            $organization,
            $subscription,
            function () use ($subscription) {
                if ($this->isStripeBacked($subscription)) {
                    app(ManageStripeSubscription::class)->cancel($subscription, false);
                } elseif (filled($subscription->chip_recurring_token)) {
                    app(CancelRecurringToken::class)->cancel($subscription);
                } else {
                    app(CancelLocalRecurringPlan::class)->cancel($subscription);
                }
            },
            $successMessage,
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
            fn () => $this->isStripeBacked($subscription)
                ? app(ManageStripeSubscription::class)->pause($subscription)
                : app(PauseLocalRecurringPlan::class)->pause($subscription),
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
            fn () => $this->isStripeBacked($subscription)
                ? app(ManageStripeSubscription::class)->resume($subscription)
                : app(ResumeLocalRecurringPlan::class)->resume($subscription),
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
            if ($this->isStripeBacked($subscription)) {
                app(ManageStripeSubscription::class)->changeAmount($subscription, (float) $data['new_amount']);
            } else {
                app(ChangeRecurringAmount::class)->change($subscription, (float) $data['new_amount']);
            }
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
        $symbol = strtoupper($currency);
        $interval = $subscription->interval->value;

        $presetOptions = [
            ['increment' => 5, 'label' => '+ '.$symbol.' 5'],
            ['increment' => 80, 'label' => '+ '.$symbol.' 80'],
            ['increment' => 100, 'label' => '+ '.$symbol.' 100'],
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
            'selectedIncrement' => $presetOptions[0]['increment'],
        ]);
    }

    public function showIncreaseLink(Organization $organization, Subscription $subscription)
    {
        abort_unless($subscription->campaign?->organization_id === $organization->getKey(), 403);

        $subscription->loadMissing('campaign');
        $expiresAt = Carbon::createFromTimestamp((int) request()->query('expires'));
        $changeAmountUrl = URL::temporarySignedRoute(
            'donorportal.subscriptions.change-amount-link',
            $expiresAt,
            ['organization' => $organization, 'subscription' => $subscription],
        );

        $symbol = strtoupper($subscription->currency);

        $presetIncrements = $this->resolvePresetIncrements();
        $selectedIncrement = $this->resolveSelectedIncrement($presetIncrements);

        $presetOptions = collect($presetIncrements)
            ->map(fn (int $increment): array => [
                'increment' => $increment,
                'label' => '+ '.$symbol.' '.$increment,
            ])
            ->all();

        return view('donor.subscription-increase', [
            'organization' => $organization,
            'subscription' => $subscription,
            'currentAmount' => (float) $subscription->amount,
            'currency' => $subscription->currency,
            'symbol' => $symbol,
            'interval' => $subscription->interval->value,
            'presetOptions' => $presetOptions,
            'selectedIncrement' => $selectedIncrement,
            'changeAmountUrl' => $changeAmountUrl,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function resolvePresetIncrements(): array
    {
        $query = request()->query('increments');

        if (! filled($query)) {
            return [5, 80, 100];
        }

        $increments = collect(explode(',', (string) $query))
            ->map(fn (string $value): int => (int) trim($value))
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();

        return $increments !== [] ? $increments : [5, 80, 100];
    }

    /**
     * @param  array<int, int>  $presetIncrements
     */
    private function resolveSelectedIncrement(array $presetIncrements): int
    {
        $selected = (int) request()->query('selected');

        if (in_array($selected, $presetIncrements, true)) {
            return $selected;
        }

        return $presetIncrements[0] ?? 5;
    }

    public function changeAmountLink(Organization $organization, Subscription $subscription)
    {
        abort_unless($subscription->campaign?->organization_id === $organization->getKey(), 403);

        $subscription->loadMissing('campaign');

        $data = request()->validate([
            'new_amount' => 'required|numeric|min:1|max:99999.99',
        ]);

        $previousAmount = (float) $subscription->amount;

        try {
            if ($this->isStripeBacked($subscription)) {
                app(ManageStripeSubscription::class)->changeAmount($subscription, (float) $data['new_amount']);
            } else {
                app(ChangeRecurringAmount::class)->change($subscription, (float) $data['new_amount']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to update subscription amount. Please try again later.'], 500);
        }

        dispatch(new SendSubscriptionAmountChangedNotification($subscription, $previousAmount));

        return response()->json([
            'success' => true,
            'new_amount' => (float) $data['new_amount'],
        ]);
    }

    public function paymentMethodClientSecret(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        if ($this->isChipBacked($subscription)) {
            return response()->json([
                'error' => 'Updating payment method is not supported for CHIP recurring subscriptions.',
            ], 422);
        }

        try {
            if ($this->isStripeBacked($subscription)) {
                $clientSecret = app(ManageStripeSubscription::class)->createSetupIntent($subscription);
            } else {
                $clientSecret = $this->createAppControlledSetupIntent($subscription);
            }

            $org = $subscription->campaign?->organization;

            return response()->json([
                'client_secret' => $clientSecret,
                'stripe_account_id' => $org?->stripe_account_id,
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['error' => 'Unable to process payment method update.'], 500);
        }
    }

    public function updatePaymentMethod(Organization $organization, Subscription $subscription)
    {
        $subscription->loadMissing('campaign');
        $this->authorizeSubscriptionAction($organization, $subscription, request()->donor);

        if ($this->isChipBacked($subscription)) {
            return response()->json([
                'error' => 'Updating payment method is not supported for CHIP recurring subscriptions.',
            ], 422);
        }

        $data = request()->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            if ($this->isStripeBacked($subscription)) {
                app(ManageStripeSubscription::class)->updatePaymentMethod($subscription, $data['payment_method_id']);
            } else {
                app(UpdateAppControlledPaymentMethod::class)->update($subscription, $data['payment_method_id']);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['error' => 'Unable to update payment method.'], 500);
        }
    }

    private function createAppControlledSetupIntent(Subscription $subscription): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing(['campaign.organization', 'donor']);
        $organization = $subscription->campaign?->organization;
        $donor = request()->donor;

        if (blank($donor?->stripe_customer_id)) {
            throw new \RuntimeException('Donor does not have a Stripe customer ID.');
        }

        $stripeOptions = $organization !== null && filled($organization->stripe_account_id)
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        $setupIntent = SetupIntent::create([
            'customer' => $donor->stripe_customer_id,
            'usage' => 'off_session',
            'payment_method_types' => ['card'],
        ], $stripeOptions);

        return $setupIntent->client_secret;
    }
}
