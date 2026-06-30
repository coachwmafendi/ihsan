<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationType;
use App\Models\Donation;
use App\Support\ChipFpxBanks;
use Chip\Builder\PurchaseBuilder;
use Chip\ChipApi;
use Chip\Exception\ChipApiException;
use Chip\Model\Purchase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use RuntimeException;

final class CreatePurchase
{
    public function create(Donation $donation, ?string $returnTo = null, ?string $preferredMethod = null, ?string $fpxBankCode = null): string
    {
        $donation->load(['campaign.organization', 'donor']);

        $chip = $this->chipClient($donation);
        $builder = $this->purchaseBuilder($donation, $returnTo);

        $paymentMethods = $donation->campaign->organization->chipPaymentMethodWhitelist();

        if ($paymentMethods !== []) {
            $builder = $builder->paymentMethodWhitelist($paymentMethods);
        }

        $result = $this->createPurchase($chip, $builder->build());

        $checkoutUrl = $this->appendPreferredParams(
            (string) $result->checkout_url,
            $preferredMethod,
            $fpxBankCode,
        );

        $donation->update([
            'chip_purchase_id' => $result->id,
            'chip_checkout_url' => $checkoutUrl,
        ]);

        return $checkoutUrl;
    }

    public function createDirectPost(Donation $donation, ?string $returnTo = null): string
    {
        $donation->load(['campaign.organization', 'donor']);

        $chip = $this->chipClient($donation);
        $builder = $this->purchaseBuilder($donation, $returnTo)
            ->paymentMethodWhitelist(PaymentMethodWhitelistMapper::cardOnly());

        $result = $this->createPurchase($chip, $builder->build());

        $donation->update([
            'chip_purchase_id' => $result->id,
            'chip_checkout_url' => $result->checkout_url,
        ]);

        return $result->direct_post_url;
    }

    private function chipClient(Donation $donation): ChipApi
    {
        try {
            return ChipApiFactory::make($donation->campaign->organization);
        } catch (InvalidArgumentException $e) {
            report($e);

            throw new RuntimeException('Failed to initialize CHIP client: '.$e->getMessage(), previous: $e);
        }
    }

    private function purchaseBuilder(Donation $donation, ?string $returnTo): PurchaseBuilder
    {
        $organization = $donation->campaign->organization;
        $campaign = $donation->campaign;
        $donor = $donation->donor;

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

        return $builder;
    }

    private function createPurchase(ChipApi $chip, Purchase $purchase): Purchase
    {
        try {
            return $chip->purchases->create($purchase);
        } catch (ChipApiException $e) {
            report($e);
            throw new RuntimeException('Failed to create CHIP purchase: '.$e->getMessage(), previous: $e);
        }
    }

    private function appendPreferredParams(string $checkoutUrl, ?string $preferredMethod, ?string $fpxBankCode): string
    {
        if ($preferredMethod !== 'fpx' || blank($fpxBankCode) || ! ChipFpxBanks::isValidB2cCode($fpxBankCode)) {
            return $checkoutUrl;
        }

        $separator = str_contains($checkoutUrl, '?') ? '&' : '?';

        return $checkoutUrl.$separator.'preferred=fpx&fpx_bank_code='.urlencode($fpxBankCode);
    }
}
