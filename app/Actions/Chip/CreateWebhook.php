<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Models\Organization;
use Chip\Exception\ChipApiException;
use Chip\Model\Webhook;
use RuntimeException;
use Throwable;

final class CreateWebhook
{
    /**
     * Ensure the organization has a registered CHIP webhook. If one already
     * exists for the application callback URL its details are refreshed.
     *
     * @throws RuntimeException
     */
    public function create(Organization $organization): Webhook
    {
        if (! $organization->chip_onboarded) {
            throw new RuntimeException('Organization is not CHIP onboarded.');
        }

        $callbackUrl = route('chip.webhook');

        try {
            $chip = ChipApiFactory::make($organization);

            foreach ($chip->webhooks->iterate() as $existing) {
                if ($existing->callback === $callbackUrl) {
                    $organization->update([
                        'chip_webhook_id' => $existing->id,
                        'chip_webhook_public_key' => $existing->public_key,
                    ]);

                    return $existing;
                }
            }

            $webhook = new Webhook;
            $webhook->title = 'Ihsan donations — '.$organization->public_id;
            $webhook->events = [
                'purchase.paid',
                'purchase.payment_failure',
                'purchase.cancelled',
                'payment.refunded',
            ];
            $webhook->callback = $callbackUrl;

            $created = $chip->webhooks->create($webhook);
        } catch (ChipApiException $e) {
            report($e);

            throw new RuntimeException('Failed to create CHIP webhook: '.$e->getMessage(), previous: $e);
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('Failed to create CHIP webhook: '.$e->getMessage(), previous: $e);
        }

        if (blank($created->id) || blank($created->public_key)) {
            throw new RuntimeException('CHIP webhook response missing ID or public key.');
        }

        $organization->update([
            'chip_webhook_id' => $created->id,
            'chip_webhook_public_key' => $created->public_key,
        ]);

        return $created;
    }
}
