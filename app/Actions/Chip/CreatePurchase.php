<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Models\Donation;
use App\Services\ChipApi;
use Illuminate\Routing\UrlGenerator;

class CreatePurchase
{
    public function __construct(
        private ChipApi $chipApi,
        private UrlGenerator $url,
    ) {}

    /**
     * Create a CHIP purchase for the donation and return the checkout URL.
     */
    public function create(Donation $donation): string
    {
        $campaign = $donation->campaign;

        if ($campaign === null) {
            throw new \RuntimeException('CHIP donations require a campaign.');
        }

        $successRedirect = $this->callbackUrl($donation, 'success');
        $failureRedirect = $this->callbackUrl($donation, 'failure');

        $forceRecurring = $donation->type === \App\Enums\DonationType::Recurring;
        $purchase = $this->chipApi->createPurchase($donation, $successRedirect, $failureRedirect, null, $forceRecurring);

        $purchaseId = $purchase['id'] ?? null;
        $checkoutUrl = $purchase['checkout_url'] ?? null;

        if (! filled($purchaseId) || ! filled($checkoutUrl)) {
            throw new \RuntimeException('CHIP purchase response missing required fields.');
        }

        $donation->update([
            'chip_purchase_id' => $purchaseId,
            'chip_checkout_url' => $checkoutUrl,
        ]);

        return $checkoutUrl;
    }

    private function callbackUrl(Donation $donation, string $status): string
    {
        $returnTo = $this->returnUrl($donation);

        return $this->url->route('chip.callback', [
            'donation' => $donation->public_id,
            'status' => $status,
            'return_to' => $returnTo,
        ]);
    }

    private function returnUrl(Donation $donation): string
    {
        $campaign = $donation->campaign;

        if ($campaign !== null && $campaign->campaign_page_enabled) {
            return route('campaigns.public', $campaign);
        }

        $source = $donation->source;

        if ($source === 'element' && filled($donation->utm_params['element_token'] ?? null)) {
            return url('/donate/'.$donation->utm_params['element_token']);
        }

        $pageUrl = $donation->page_url;

        if (filled($pageUrl)) {
            return $pageUrl;
        }

        return route('home');
    }
}
