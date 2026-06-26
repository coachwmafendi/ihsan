<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Subscription;
use Chip\Builder\PurchaseBuilder;
use Chip\Exception\ChipApiException;
use Illuminate\Support\Facades\Route;
use RuntimeException;

final class ChargeRecurringInstallment
{
    public function handle(Subscription $subscription): void
    {
        $subscription->loadMissing(['campaign.organization', 'donor']);

        $organization = $subscription->campaign?->organization;

        if ($organization === null) {
            throw new RuntimeException('Subscription is not linked to an organization.');
        }

        $donor = $subscription->donor;

        if ($donor === null) {
            throw new RuntimeException('Subscription is not linked to a donor.');
        }

        if (blank($subscription->chip_recurring_token)) {
            throw new RuntimeException('Subscription does not have a CHIP recurring token.');
        }

        $chip = ChipApiFactory::make($organization);

        $builder = PurchaseBuilder::create()
            ->brandId($organization->chip_brand_id)
            ->currency($subscription->currency)
            ->language('en')
            ->clientEmail($donor->email)
            ->clientFullName($donor->name)
            ->addProduct($subscription->campaign->title, (int) round((float) $subscription->amount * 100));

        if (Route::has('chip.webhook')) {
            $builder = $builder->successCallback(route('chip.webhook'));
        }

        $purchase = $builder->build();

        try {
            $createdPurchase = $chip->purchases->create($purchase);
            $result = $chip->purchases->charge($createdPurchase->id, $subscription->chip_recurring_token);
        } catch (ChipApiException $e) {
            report($e);

            throw new RuntimeException('Failed to charge CHIP recurring installment: '.$e->getMessage(), previous: $e);
        }

        $donation = $subscription->donations()->create([
            'campaign_id' => $subscription->campaign_id,
            'donor_id' => $subscription->donor_id,
            'currency' => $subscription->currency,
            'gross_amount' => $subscription->amount,
            'status' => $result->status === 'paid' ? DonationStatus::Succeeded : DonationStatus::Pending,
            'type' => DonationType::Recurring,
            'source' => $subscription->source ?? 'checkout_modal',
            'chip_purchase_id' => $result->id,
        ]);

        app(SyncDonationDetails::class)->sync($donation);
    }
}
