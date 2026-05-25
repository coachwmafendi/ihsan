<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Console\Command;

class SeedDonationsTest extends Command
{
    protected $signature = 'donations:seed-test {--email=fatimah.zahra6@example.com}';

    protected $description = 'Seed test donations via the donation form flow';

    public function handle(): int
    {
        $email = $this->option('email');

        $element = Element::where('token', '12345')->first();
        if (! $element) {
            $this->error('Element with token 12345 not found');

            return self::FAILURE;
        }

        $organization = $element->organization;
        $campaign = $element->campaign;

        $this->info("Element: {$element->name} (token: {$element->token})");
        $this->info("Organization: {$organization->name}");
        $this->info("Campaign: {$campaign?->title} (ID: {$campaign?->id})");
        $this->info('');

        $donor = Donor::firstOrCreate(
            ['email' => $email],
            ['name' => 'Fatimah Zahra', 'phone' => '+60123456789']
        );
        $this->info("Donor: {$donor->name} <{$donor->email}> (ID: {$donor->id})");

        $onetimes = [10, 15.50, 22, 30, 38.80, 45, 55];
        $monthlies = [20, 45, 60, 100, 130];

        $this->newLine();
        $this->info('--- One-Time Donations ---');

        foreach ($onetimes as $amount) {
            $donation = $this->createDonation($donor, $organization, $campaign, $amount, DonationType::OneTime);
            SendNewDonationNotification::dispatch($donation);
            SendDonationReceipt::dispatch($donation);
            $this->line("  RM {$amount} — donation #{$donation->id} (pi: {$donation->stripe_payment_intent_id})");
        }

        $this->newLine();
        $this->info('--- Monthly Donations ---');

        foreach ($monthlies as $amount) {
            $subscription = $this->createSubscription($donor, $organization, $campaign, $amount);
            $donation = $this->createDonation($donor, $organization, $campaign, $amount, DonationType::Recurring, $subscription);
            SendNewDonationNotification::dispatch($donation);
            SendNewSubscriptionNotification::dispatch($donation);
            SendDonationReceipt::dispatch($donation);
            $this->line("  RM {$amount} — sub #{$subscription->id} → donation #{$donation->id}");
        }

        $campaignTotal = Donation::where('campaign_id', $campaign->id)
            ->where('status', DonationStatus::Succeeded)
            ->sum('gross_amount');
        $campaign->update(['collected_amount' => $campaignTotal]);

        $this->newLine();
        $this->info("✔ Campaign collected_amount updated to RM {$campaignTotal}");
        $this->info('✔ Done!');

        return self::SUCCESS;
    }

    private function createDonation(
        Donor $donor,
        Organization $organization,
        ?Campaign $campaign,
        float $amount,
        DonationType $type,
        ?Subscription $subscription = null,
    ): Donation {
        $gross = $amount;
        $stripeFee = round($gross * 0.036 + 0.50, 2);
        $processingFee = round($gross * 0.025, 2);
        $net = round($gross - $stripeFee - $processingFee, 2);

        return Donation::create([
            'campaign_id' => $campaign?->id,
            'donor_id' => $donor->id,
            'subscription_id' => $subscription?->id,
            'stripe_payment_intent_id' => 'pi_test_'.str()->random(24),
            'stripe_charge_id' => 'ch_test_'.str()->random(24),
            'payment_method_brand' => 'visa',
            'payment_method_type' => 'card',
            'donor_country' => 'MY',
            'gross_amount' => $gross,
            'stripe_fee' => $stripeFee,
            'processing_fee' => $processingFee,
            'net_amount' => $net,
            'currency' => 'myr',
            'status' => DonationStatus::Succeeded,
            'type' => $type,
            'is_anonymous' => false,
        ]);
    }

    private function createSubscription(
        Donor $donor,
        Organization $organization,
        ?Campaign $campaign,
        float $amount,
    ): Subscription {
        return Subscription::create([
            'campaign_id' => $campaign?->id,
            'donor_id' => $donor->id,
            'organization_id' => $organization->id,
            'stripe_subscription_id' => 'sub_test_'.str()->random(24),
            'stripe_customer_id' => 'cus_test_'.str()->random(24),
            'stripe_price_id' => 'price_test_'.str()->random(24),
            'currency' => 'myr',
            'amount' => $amount,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }
}
