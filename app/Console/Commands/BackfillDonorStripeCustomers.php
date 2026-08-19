<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Stripe\ResolveDonorStripeCustomer;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Throwable;

#[Signature('app:backfill-donor-stripe-customers {--dry-run} {--limit=}')]
#[Description('Rebuild the donor-to-Stripe-customer map, one entry per connected account')]
class BackfillDonorStripeCustomers extends Command
{
    public function handle(ResolveDonorStripeCustomer $resolver): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        Stripe::setApiKey(config('services.stripe.secret'));

        $pairs = $this->pairsNeedingBackfill($limit);

        if ($pairs->isEmpty()) {
            $this->info('Every donor/organization pair already has a Stripe customer mapping.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d donor/organization pair(s) need a mapping.', $pairs->count()));

        $resolved = 0;
        $unresolved = 0;

        foreach ($pairs as $pair) {
            $donor = Donor::find($pair->donor_id);
            $organization = Organization::find($pair->organization_id);

            if ($donor === null || $organization === null) {
                $unresolved++;

                continue;
            }

            try {
                $customerId = $this->discoverCustomerId($donor, $organization);
            } catch (Throwable $e) {
                report($e);
                $this->warn(sprintf('donor #%d / org #%d: %s', $donor->getKey(), $organization->getKey(), $e->getMessage()));
                $unresolved++;

                continue;
            }

            if ($customerId === null) {
                $this->warn(sprintf('donor #%d / org #%d: no Stripe customer could be identified.', $donor->getKey(), $organization->getKey()));
                $unresolved++;

                continue;
            }

            $this->line(sprintf('donor #%d / org #%d -> %s', $donor->getKey(), $organization->getKey(), $customerId));

            if (! $dryRun) {
                $resolver->remember($donor, $organization, $customerId);
            }

            $resolved++;
        }

        $this->newLine();
        $this->info(sprintf('%s %d mapping(s); %d unresolved.', $dryRun ? 'Would write' : 'Wrote', $resolved, $unresolved));

        return self::SUCCESS;
    }

    /**
     * Every donor/organization pair that has transacted but has no mapping yet.
     *
     * @return Collection<int, object{donor_id: int, organization_id: int}>
     */
    private function pairsNeedingBackfill(?int $limit): Collection
    {
        $query = Donation::query()
            ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
            ->whereNotNull('donations.donor_id')
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('donor_stripe_customers')
                    ->whereColumn('donor_stripe_customers.donor_id', 'donations.donor_id')
                    ->whereColumn('donor_stripe_customers.organization_id', 'campaigns.organization_id');
            })
            ->distinct()
            ->select(['donations.donor_id', 'campaigns.organization_id']);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Ask Stripe which customer this donor is on the organization's account.
     *
     * Saved payment methods and payment intents both name their customer, and
     * both already live on the correct account, so either identifies it without
     * guessing.
     */
    private function discoverCustomerId(Donor $donor, Organization $organization): ?string
    {
        $stripeOptions = $organization->stripeOptions();

        $subscriptions = Subscription::query()
            ->with('donorPaymentMethod')
            ->join('campaigns', 'campaigns.id', '=', 'subscriptions.campaign_id')
            ->where('subscriptions.donor_id', $donor->getKey())
            ->where('campaigns.organization_id', $organization->getKey())
            ->select('subscriptions.*')
            ->get();

        foreach ($subscriptions as $subscription) {
            $paymentMethodId = $subscription->donorPaymentMethod?->stripe_payment_method_id;

            if (filled($paymentMethodId)) {
                $customerId = $this->customerFrom(fn () => PaymentMethod::retrieve($paymentMethodId, $stripeOptions)->customer);

                if ($customerId !== null) {
                    return $customerId;
                }
            }

            if (filled($subscription->stripe_subscription_id)) {
                $customerId = $this->customerFrom(
                    fn () => StripeSubscription::retrieve($subscription->stripe_subscription_id, $stripeOptions)->customer
                );

                if ($customerId !== null) {
                    return $customerId;
                }
            }
        }

        $paymentIntentIds = Donation::query()
            ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
            ->where('donations.donor_id', $donor->getKey())
            ->where('campaigns.organization_id', $organization->getKey())
            ->whereNotNull('donations.stripe_payment_intent_id')
            ->orderByDesc('donations.id')
            ->pluck('donations.stripe_payment_intent_id');

        foreach ($paymentIntentIds as $paymentIntentId) {
            $customerId = $this->customerFrom(fn () => PaymentIntent::retrieve($paymentIntentId, $stripeOptions)->customer);

            if ($customerId !== null) {
                return $customerId;
            }
        }

        return null;
    }

    private function customerFrom(callable $fetch): ?string
    {
        try {
            $customer = $fetch();
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (blank($customer)) {
            return null;
        }

        return is_string($customer) ? $customer : $customer->id;
    }
}
