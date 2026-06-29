<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Models\Donation;
use App\Models\Organization;
use RuntimeException;
use Throwable;

final class VerifyWebhook
{
    /**
     * Verify a CHIP webhook/callback payload signature.
     *
     * Webhook API payloads are signed with the dedicated per-webhook public key.
     * Purchase success callbacks are signed with the company-wide public key.
     *
     * @throws RuntimeException
     */
    public function verify(string $payload, ?string $signature, ?Organization $organization = null): Organization
    {
        if (empty($signature)) {
            throw new RuntimeException('Missing CHIP webhook signature.');
        }

        $data = json_decode($payload, true);

        if ($organization === null) {
            $organization = $this->resolveOrganization($data);
        }

        if (! $organization->chip_onboarded) {
            throw new RuntimeException('Organization is not CHIP onboarded.');
        }

        if (filled($organization->chip_webhook_public_key)) {
            if (ChipApiFactory::verifyWebhook($payload, $signature, $organization->chip_webhook_public_key)) {
                return $organization;
            }
        }

        if (filled($organization->chip_webhook_id)) {
            try {
                $webhookPublicKey = $this->fetchWebhookPublicKey($organization);

                if (ChipApiFactory::verifyWebhook($payload, $signature, $webhookPublicKey)) {
                    $organization->update(['chip_webhook_public_key' => $webhookPublicKey]);

                    return $organization;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $fallbackKey = ChipApiFactory::getPublicKey($organization);

        if (ChipApiFactory::verifyWebhook($payload, $signature, $fallbackKey)) {
            return $organization;
        }

        throw new RuntimeException('Invalid CHIP webhook signature.');
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws RuntimeException
     */
    private function resolveOrganization(array $data): Organization
    {
        $purchaseId = $this->purchaseIdFromPayload($data);

        if (! is_string($purchaseId) || blank($purchaseId)) {
            throw new RuntimeException('Missing CHIP purchase ID in webhook payload.');
        }

        $donation = Donation::where('chip_purchase_id', $purchaseId)->first();

        if ($donation === null || $donation->campaign?->organization === null) {
            throw new RuntimeException('CHIP purchase not recognized.');
        }

        return $donation->campaign->organization;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function purchaseIdFromPayload(array $data): ?string
    {
        $eventType = $data['event_type'] ?? null;

        if ($eventType === 'payment.refunded') {
            $relatedTo = is_array($data['related_to'] ?? null) ? $data['related_to'] : [];

            return $relatedTo['id'] ?? null;
        }

        return $data['id'] ?? null;
    }

    /**
     * @throws RuntimeException
     */
    private function fetchWebhookPublicKey(Organization $organization): string
    {
        return cache()->remember(
            "chip_webhook_public_key:{$organization->chip_webhook_id}",
            3600,
            function () use ($organization): string {
                $webhook = ChipApiFactory::make($organization)->webhooks->get($organization->chip_webhook_id);
                $key = $webhook->public_key ?? null;

                if (! is_string($key) || blank($key)) {
                    throw new RuntimeException('CHIP webhook public key is missing.');
                }

                return $key;
            }
        );
    }
}
