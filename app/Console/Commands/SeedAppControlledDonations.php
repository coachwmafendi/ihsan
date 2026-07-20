<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Element;
use App\Models\ProcessingFee;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('donations:seed-app-controlled {campaign=IH9W4CMV : Public ID of the campaign} {--element=E4GUMJJS : Public ID of the element} {--one-time-count=4 : One-time donations per currency} {--monthly-count=4 : Monthly donations (subscriptions) per currency} {--one-time-currencies=myr,usd : One-time currencies} {--monthly-currencies=myr,sgd : Monthly currencies} {--start=2026-07-10 : Earliest date when not using --on} {--end= : Latest date, defaults to today} {--on= : Fixed datetime in MYT (DD/MM/YYYY HH:MM)} {--min=30 : Minimum amount} {--max=500 : Maximum amount}')]
#[Description('Seed app-controlled test donations (RECURRING_USE_APP_CONTROLLED=true) for a campaign')]
class SeedAppControlledDonations extends Command
{
    private Campaign $campaign;

    private Element $element;

    private float $processingFeePercent;

    private ?CarbonImmutable $fixedDate = null;

    public function handle(): int
    {
        $this->campaign = Campaign::where('public_id', $this->argument('campaign'))->firstOrFail();
        $this->element = Element::where('public_id', $this->option('element'))->firstOrFail();
        $organization = $this->campaign->organization;

        $this->processingFeePercent = (float) ($organization->processing_fee_override ?? config('services.stripe.processing_fee_percent', 2.5));

        $start = CarbonImmutable::parse($this->option('start'))->startOfDay();
        $end = $this->option('end')
            ? CarbonImmutable::parse($this->option('end'))->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        if ($this->option('on')) {
            $parsed = CarbonImmutable::createFromFormat('d/m/Y H:i', $this->option('on'), 'Asia/Kuala_Lumpur');

            if ($parsed === false || $parsed === null) {
                $this->error('Invalid --on datetime. Expected format: DD/MM/YYYY HH:MM (MYT).');

                return self::FAILURE;
            }

            $this->fixedDate = $parsed->setTimezone('UTC');
        }

        $oneTimeCurrencies = $this->parseCurrencies($this->option('one-time-currencies'));
        $monthlyCurrencies = $this->parseCurrencies($this->option('monthly-currencies'));
        $oneTimeCount = (int) $this->option('one-time-count');
        $monthlyCount = (int) $this->option('monthly-count');

        $this->info('Campaign: '.$this->campaign->title.' ('.$this->campaign->public_id.')');
        $this->info('Element: '.$this->element->name.' ('.$this->element->public_id.')');
        $this->info('Mode: app-controlled (no Stripe Subscription)');

        if ($this->fixedDate) {
            $this->info('Fixed datetime (MYT): '.$this->fixedDate->timezone('Asia/Kuala_Lumpur')->toDateTimeString());
        } else {
            $this->info("Date range: {$start->toDateString()} → {$end->toDateString()}");
        }

        $this->info("One-time: {$oneTimeCount} each of ".strtoupper(implode(', ', $oneTimeCurrencies)));
        $this->info("Monthly: {$monthlyCount} each of ".strtoupper(implode(', ', $monthlyCurrencies)));
        $this->newLine();

        $created = 0;

        $this->info('--- One-time donations ---');
        foreach ($oneTimeCurrencies as $currency) {
            for ($i = 1; $i <= $oneTimeCount; $i++) {
                [$donor, $paymentMethod] = $this->createDonorWithPaymentMethod($currency);
                $date = $this->resolveDate($start, $end);
                $donation = $this->persistDonation(
                    donor: $donor,
                    paymentMethod: $paymentMethod,
                    amount: $this->randomAmount(),
                    currency: $currency,
                    date: $date,
                    type: DonationType::OneTime,
                    subscription: null,
                    processingFeePercent: $this->processingFeePercent,
                    paymentIntentId: $this->stripeId('pi'),
                    chargeId: $this->stripeId('ch'),
                );
                $created++;
                $this->line('  ['.strtoupper($currency).'] '.number_format($donation->gross_amount, 2).' → '.$donation->public_id.' @ '.$donation->created_at->toDateTimeString());
            }
        }

        $this->info('--- Monthly app-controlled subscriptions ---');
        foreach ($monthlyCurrencies as $currency) {
            for ($i = 1; $i <= $monthlyCount; $i++) {
                $amount = $this->randomAmount();
                [$donor, $paymentMethod] = $this->createDonorWithPaymentMethod($currency);
                $date = $this->resolveDate($start, $end);

                $subscription = Subscription::create([
                    'campaign_id' => $this->campaign->id,
                    'donor_id' => $donor->id,
                    'donor_payment_method_id' => $paymentMethod->id,
                    'source' => 'element',
                    'amount' => $amount,
                    'currency' => $currency,
                    'interval' => SubscriptionInterval::Monthly,
                    'status' => SubscriptionStatus::Active,
                    'payment_count' => 1,
                    'cover_fee' => false,
                    'retry_count' => 0,
                    'current_period_start' => $date,
                    'current_period_end' => $date->addMonth(),
                    'next_charge_at' => $date->addMonth(),
                    'last_charge_at' => $date,
                ]);
                $subscription->created_at = $date;
                $subscription->updated_at = $date;
                $subscription->saveQuietly();

                $donation = $this->persistDonation(
                    donor: $donor,
                    paymentMethod: $paymentMethod,
                    amount: $amount,
                    currency: $currency,
                    date: $date,
                    type: DonationType::Recurring,
                    subscription: $subscription,
                    processingFeePercent: $this->processingFeePercent,
                    paymentIntentId: $this->stripeId('pi'),
                    chargeId: $this->stripeId('ch'),
                );
                $created++;
                $this->line('  ['.strtoupper($currency).'] '.number_format($donation->gross_amount, 2).' → sub '.$subscription->public_id.', donation '.$donation->public_id.' @ '.$donation->created_at->toDateTimeString());
            }
        }

        $total = Donation::where('campaign_id', $this->campaign->id)
            ->where('status', DonationStatus::Succeeded)
            ->sum('base_amount');
        $this->campaign->update(['collected_amount' => $total]);

        $this->newLine();
        $this->info("Created {$created} app-controlled donations.");
        $this->info('Campaign collected_amount: '.$this->campaign->fresh()->collected_amount);

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function parseCurrencies(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (string $currency): string => strtolower(trim($currency)),
            explode(',', $value)
        ))));
    }

    /**
     * @return array{0: Donor, 1: DonorPaymentMethod}
     */
    private function createDonorWithPaymentMethod(string $currency): array
    {
        $donor = Donor::factory()->create();
        $donor->stripe_customer_id = $this->stripeId('cus');
        $donor->saveQuietly();

        $paymentMethod = DonorPaymentMethod::create([
            'donor_id' => $donor->id,
            'stripe_payment_method_id' => $this->stripeId('pm'),
            'brand' => 'visa',
            'last4' => (string) random_int(1000, 9999),
            'exp_month' => random_int(1, 12),
            'exp_year' => now()->year + random_int(1, 5),
            'country' => $this->countryForCurrency($currency),
            'is_default' => true,
        ]);

        return [$donor, $paymentMethod];
    }

    private function persistDonation(
        Donor $donor,
        DonorPaymentMethod $paymentMethod,
        float $amount,
        string $currency,
        CarbonImmutable $date,
        DonationType $type,
        ?Subscription $subscription,
        float $processingFeePercent,
        string $paymentIntentId,
        string $chargeId,
    ): Donation {
        $rate = $this->exchangeRate($currency);
        $gross = $amount;

        if ($currency === 'myr') {
            $baseAmount = $gross;
            $exchangeRate = null;
        } else {
            $baseAmount = round($gross * $rate, 2);
            $exchangeRate = $rate;
        }

        $processingFee = round($baseAmount * $processingFeePercent / 100, 2);
        $stripeFee = round($baseAmount * 0.036 + 0.50, 2);
        $netAmount = round($baseAmount - $processingFee - $stripeFee, 2);

        $donation = Donation::create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $donor->id,
            'subscription_id' => $subscription?->id,
            'source' => 'element',
            'stripe_payment_intent_id' => $paymentIntentId,
            'stripe_charge_id' => $chargeId,
            'payment_method_brand' => 'visa',
            'payment_method_type' => 'card',
            'donor_country' => $this->countryForCurrency($currency),
            'payment_method_last4' => (string) random_int(1000, 9999),
            'gross_amount' => $gross,
            'base_currency' => 'myr',
            'base_amount' => $baseAmount,
            'exchange_rate' => $exchangeRate,
            'stripe_fee' => $stripeFee,
            'processing_fee' => $processingFee,
            'net_amount' => $netAmount,
            'currency' => $currency,
            'status' => DonationStatus::Succeeded,
            'type' => $type,
            'is_anonymous' => false,
            'utm_params' => [
                'source' => 'element',
                'element_token' => $this->element->token,
                'element_type' => 'button',
                'element_name' => $this->element->name,
            ],
            'finalized_at' => $date,
            'new_donation_notification_sent_at' => $date,
            'large_donation_notification_sent_at' => $date,
            'receipt_sent_at' => $date,
        ]);

        $donation->created_at = $date;
        $donation->updated_at = $date;
        $donation->saveQuietly();

        $feeStatus = $this->campaign->organization->fee_collection_method === 'upfront' ? 'collected' : 'pending';
        $fee = ProcessingFee::create([
            'donation_id' => $donation->id,
            'organization_id' => $this->campaign->organization_id,
            'fee_amount' => $processingFee,
            'fee_percentage' => $processingFeePercent,
            'status' => $feeStatus,
        ]);
        $fee->created_at = $date;
        $fee->updated_at = $date;
        $fee->saveQuietly();

        return $donation;
    }

    private function randomAmount(): float
    {
        $min = (int) $this->option('min');
        $max = (int) $this->option('max');

        $minSteps = max(1, (int) floor($min / 5));
        $maxSteps = max($minSteps, (int) floor($max / 5));

        return (float) (random_int($minSteps, $maxSteps) * 5);
    }

    private function resolveDate(CarbonImmutable $start, CarbonImmutable $end): CarbonImmutable
    {
        if ($this->fixedDate !== null) {
            return $this->fixedDate;
        }

        $startSeconds = $start->timestamp;
        $endSeconds = $end->timestamp;

        return CarbonImmutable::createFromTimestamp(random_int($startSeconds, $endSeconds));
    }

    private function stripeId(string $prefix): string
    {
        return $prefix.'_'.str()->random(24);
    }

    private function exchangeRate(string $currency): float
    {
        return match ($currency) {
            'myr' => 1.0,
            'sgd' => 3.40,
            'usd' => 4.70,
            default => 1.0,
        };
    }

    private function countryForCurrency(string $currency): string
    {
        return match ($currency) {
            'myr' => 'MY',
            'sgd' => 'SG',
            'usd' => 'US',
            default => 'MY',
        };
    }
}
