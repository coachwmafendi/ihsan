<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationType;
use App\Models\Donation;
use Chip\Builder\PurchaseBuilder;
use Chip\Exception\ChipApiException;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use RuntimeException;

final class CreatePurchase
{
    public function create(Donation $donation, ?string $returnTo = null): string
    {
        $donation->load(['campaign.organization', 'donor']);

        $organization = $donation->campaign->organization;
        $campaign = $donation->campaign;
        $donor = $donation->donor;

        try {
            $chip = ChipApiFactory::make($organization);
        } catch (InvalidArgumentException $e) {
            report($e);

            throw new RuntimeException('Failed to initialize CHIP client: '.$e->getMessage(), previous: $e);
        }

        $successParams = ['donation' => $donation->public_id, 'status' => 'success'];
        $failureParams = ['donation' => $donation->public_id, 'status' => 'failure'];
        $cancelParams = ['donation' => $donation->public_id, 'status' => 'cancelled'];

        if (filled($returnTo)) {
            $successParams['return_to'] = $returnTo;
            $failureParams['return_to'] = $returnTo;
            $cancelParams['return_to'] = $returnTo;
        }

        $builder = PurchaseBuilder::create()
            ->brandId($organization->chip_brand_id)
            ->currency(strtoupper($donation->currency))
            ->language('en')
            ->clientEmail($donor->email)
            ->clientFullName($donor->name)
            ->addProduct($campaign->title, (int) round(((float) $donation->gross_amount + (float) ($donation->donor_fee_covered ?? 0)) * 100))
            ->successRedirect(route('chip.callback', $successParams))
            ->failureRedirect(route('chip.callback', $failureParams))
            ->cancelRedirect(route('chip.callback', $cancelParams));

        if (Route::has('chip.webhook')) {
            $builder = $builder->successCallback(route('chip.webhook'));
        }

        if ($donation->type === DonationType::Recurring) {
            $builder = $builder->forceRecurring(true);
        }

        $paymentMethods = $organization->chipPaymentMethodWhitelist();

        if ($paymentMethods !== []) {
            $builder = $builder->paymentMethodWhitelist($paymentMethods);
        }

        $purchase = $builder->build();

        try {
            $result = $chip->purchases->create($purchase);
        } catch (ChipApiException $e) {
            report($e);
            throw new RuntimeException('Failed to create CHIP purchase: '.$e->getMessage(), previous: $e);
        }

        $donation->update([
            'chip_purchase_id' => $result->id,
            'chip_checkout_url' => $result->checkout_url,
        ]);

        return $result->checkout_url;
    }
}
