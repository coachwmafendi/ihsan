<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Models\Donation;
use Chip\Builder\PurchaseBuilder;
use Chip\Exception\ChipApiException;
use RuntimeException;

final class CreatePurchase
{
    public function create(Donation $donation): string
    {
        $donation->load(['campaign.organization', 'donor']);

        $organization = $donation->campaign->organization;
        $campaign = $donation->campaign;
        $donor = $donation->donor;

        $chip = ChipApiFactory::make($organization);

        $purchase = PurchaseBuilder::create()
            ->brandId($organization->chip_brand_id)
            ->currency($donation->currency)
            ->language('en')
            ->clientEmail($donor->email)
            ->clientFullName($donor->name)
            ->addProduct($campaign->title, (int) $donation->gross_amount)
            ->successRedirect(route('chip.callback', ['donation' => $donation->public_id, 'status' => 'success']))
            ->failureRedirect(route('chip.callback', ['donation' => $donation->public_id, 'status' => 'failure']))
            ->successCallback(route('chip.webhook'))
            ->build();

        try {
            $result = $chip->purchases->create($purchase);
        } catch (ChipApiException $e) {
            report($e);
            throw new RuntimeException('Failed to create CHIP purchase: '.$e->getMessage());
        }

        $donation->update([
            'chip_purchase_id' => $result->id,
            'chip_checkout_url' => $result->checkout_url,
        ]);

        return $result->checkout_url;
    }
}
