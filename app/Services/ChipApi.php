<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Donation;
use App\Models\Organization;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ChipApi
{
    private function client(Organization $organization): PendingRequest
    {
        $apiKey = $organization->chipApiKey();

        if (! filled($apiKey)) {
            throw new \RuntimeException('CHIP API key is not configured for this organization.');
        }

        return Http::baseUrl(config('services.chip.api_base_url'))
            ->withToken($apiKey, 'Bearer')
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Create a CHIP purchase and return the full purchase payload.
     *
     * @return array<string, mixed>
     */
    public function createPurchase(Donation|array $donation, string $successRedirect, string $failureRedirect, ?string $successCallback = null, bool $forceRecurring = false): array
    {
        $donation = is_array($donation) ? (object) $donation : $donation;

        $organization = $this->requireOrganization($donation);
        $brandId = $organization->chipBrandId();

        if (! filled($brandId)) {
            throw new \RuntimeException('CHIP Brand ID is not configured for this organization.');
        }

        $grossAmount = (float) $donation->gross_amount;
        $feeCovered = (float) $donation->donor_fee_covered;
        $totalAmountCents = (int) round(($grossAmount + $feeCovered) * 100);

        $payload = [
            'brand_id' => $brandId,
            'client' => [
                'email' => $donation->donor->email,
                'full_name' => trim("{$donation->donor->first_name} {$donation->donor->last_name}") ?: $donation->donor->email,
                'phone' => $donation->donor->phone,
            ],
            'purchase' => [
                'currency' => strtoupper($donation->currency),
                'products' => [
                    [
                        'name' => $donation->campaign?->title ?? 'Donation',
                        'price' => $totalAmountCents,
                        'quantity' => 1,
                    ],
                ],
                'language' => app()->getLocale() === 'ms' ? 'ms' : 'en',
            ],
            'success_redirect' => $successRedirect,
            'failure_redirect' => $failureRedirect,
            'success_callback' => $successCallback,
            'send_receipt' => false,
            'platform' => 'web',
            'force_recurring' => $forceRecurring,
            'reference' => $donation->public_id,
            'metadata' => [
                'donation_id' => $donation->public_id,
                'campaign_id' => $donation->campaign?->public_id,
            ],
        ];

        $paymentMethods = $organization->chipPaymentMethods();
        if ($forceRecurring) {
            $payload['payment_method_whitelist'] = ['visa', 'mastercard', 'maestro'];
        } elseif ($paymentMethods !== [] && $paymentMethods !== ['card']) {
            $payload['payment_method_whitelist'] = $paymentMethods;
        }

        $response = $this->client($organization)->post('/purchases/', $payload);

        if ($response->failed()) {
            throw new \RuntimeException('CHIP purchase creation failed: '.($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPurchase(string $purchaseId, Organization $organization): ?array
    {
        if (! filled($purchaseId)) {
            return null;
        }

        $response = $this->client($organization)->get("/purchases/{$purchaseId}/");

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new \RuntimeException('CHIP purchase retrieval failed: '.($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Charge a CHIP purchase using a recurring token.
     *
     * @return array<string, mixed>
     */
    public function chargePurchase(string $purchaseId, string $recurringToken, Organization $organization): array
    {
        $response = $this->client($organization)->post("/purchases/{$purchaseId}/charge/", [
            'recurring_token' => $recurringToken,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('CHIP recurring charge failed: '.($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Delete a CHIP recurring token.
     */
    public function deleteRecurringToken(string $tokenPurchaseId, Organization $organization): void
    {
        $response = $this->client($organization)->post("/purchases/{$tokenPurchaseId}/delete_recurring_token/");

        if ($response->failed() && $response->status() !== 404) {
            throw new \RuntimeException('CHIP recurring token deletion failed: '.($response->json('detail') ?? $response->body()));
        }
    }

    private function requireOrganization(object $donation): Organization
    {
        $organization = $donation->campaign?->organization;

        if (! $organization instanceof Organization) {
            throw new \RuntimeException('Donation is not linked to a campaign organization.');
        }

        return $organization;
    }
}
