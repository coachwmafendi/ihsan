<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Chip\VerifyWebhook;
use App\Enums\DonationStatus;
use App\Jobs\ProcessChipWebhook;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ChipWebhookController extends Controller
{
    public function __construct(private VerifyWebhook $verifyWebhook) {}

    public function __invoke(Request $request, ?Organization $organization = null): JsonResponse|Response
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Signature');

        try {
            $organization = $this->verifyWebhook->verify($payload, $signature, $organization);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $event = $request->all();
        $eventType = $event['event_type'] ?? 'purchase.updated';
        $eventId = $this->eventId($event, $eventType);

        if (blank($eventId)) {
            return response()->json(['error' => 'Missing event ID'], 400);
        }

        $log = WebhookLog::firstOrCreate(
            [
                'gateway' => 'chip',
                'stripe_event_id' => $eventId,
            ],
            [
                'event_type' => $eventType,
                'payload' => $event,
                'status' => 'received',
            ]
        );

        // CHIP identifies an event by its purchase ID, so the log row is shared
        // by every event for that purchase. A replay repeats the type already
        // stored; a progression (created -> paid) carries a new one and must
        // still be processed.
        if (! $log->wasRecentlyCreated) {
            if ($log->event_type === $eventType) {
                return response()->json(['received' => true, 'duplicate' => true]);
            }

            $log->update([
                'event_type' => $eventType,
                'payload' => $event,
            ]);
        }

        if (str_starts_with($eventType, 'purchase.')) {
            ProcessChipWebhook::dispatch($event);

            return response()->json(['received' => true]);
        }

        if ($eventType === 'payment.refunded') {
            $this->handlePaymentRefunded($event, $organization);

            return response()->json(['received' => true]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function eventId(array $event, string $eventType): ?string
    {
        if ($eventType === 'payment.refunded') {
            return $event['id'] ?? null;
        }

        return $event['id'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handlePaymentRefunded(array $event, Organization $organization): void
    {
        $relatedTo = is_array($event['related_to'] ?? null) ? $event['related_to'] : [];
        $purchaseId = $relatedTo['id'] ?? null;

        if (! is_string($purchaseId) || blank($purchaseId)) {
            return;
        }

        $donation = Donation::where('chip_purchase_id', $purchaseId)
            ->whereHas('campaign', fn ($query) => $query->where('organization_id', $organization->id))
            ->first();

        if ($donation === null) {
            return;
        }

        $donation->update([
            'status' => DonationStatus::Refunded,
            'refunded_at' => $donation->refunded_at ?? now(),
        ]);
    }
}
