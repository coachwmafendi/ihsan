<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DonorEmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailWebhookService
{
    public function processMailgun(Request $request): void
    {
        if (! $this->verifyMailgun($request)) {
            abort(401, 'Invalid Mailgun signature.');
        }

        $event = $request->input('event-data.event');
        $messageId = $request->input('event-data.message.headers.message-id');
        $reason = $request->input('event-data.delivery-status.message')
            ?: $request->input('event-data.delivery-status.description');
        $severity = $request->input('event-data.severity');

        $messageId = $this->normalizeMessageId((string) $messageId);

        $this->updateStatus($event, $messageId, $reason, $severity);
    }

    public function processPostmark(Request $request): void
    {
        $secret = config('services.postmark.webhook_secret');

        if (filled($secret) && $request->input('secret') !== $secret) {
            abort(401, 'Invalid Postmark webhook secret.');
        }

        $type = $request->input('RecordType');
        $metadataId = $request->input('Metadata.donor_email_log_message_id');
        $providerMessageId = $request->input('MessageID');

        // Prefer metadata correlation because Postmark MessageID is provider-specific.
        $messageId = filled($metadataId)
            ? (string) $metadataId
            : $this->normalizeMessageId((string) $providerMessageId);

        $reason = $request->input('Description')
            ?: $request->input('BounceSummary.Description')
            ?: $request->input('Details');

        $this->updateStatus(
            event: $this->normalizePostmarkEvent($type),
            messageId: $messageId,
            reason: $reason,
        );
    }

    private function verifyMailgun(Request $request): bool
    {
        $apiKey = config('services.mailgun.secret');

        if (blank($apiKey)) {
            Log::warning('Mailgun webhook received but no secret is configured.');

            return true;
        }

        $timestamp = $request->input('signature.timestamp');
        $token = $request->input('signature.token');
        $signature = $request->input('signature.signature');

        if (blank($timestamp) || blank($token) || blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$token, $apiKey);

        return hash_equals($expected, $signature);
    }

    private function updateStatus(string $event, string $messageId, ?string $reason = null, ?string $severity = null): void
    {
        if (blank($messageId)) {
            return;
        }

        $log = DonorEmailLog::query()
            ->where('message_id', $messageId)
            ->orWhere('provider_message_id', $messageId)
            ->first();

        if ($log === null) {
            return;
        }

        switch ($event) {
            case 'delivered':
                $log->update([
                    'delivery_status' => 'delivered',
                    'delivered_at' => now(),
                ]);
                break;

            case 'failed':
                if ($severity === 'permanent') {
                    $log->update([
                        'delivery_status' => 'bounced',
                        'bounced_at' => now(),
                        'bounce_reason' => $reason,
                    ]);
                }
                break;

            case 'bounced':
                $log->update([
                    'delivery_status' => 'bounced',
                    'bounced_at' => now(),
                    'bounce_reason' => $reason,
                ]);
                break;

            case 'complained':
                $log->update([
                    'delivery_status' => 'complained',
                    'complained_at' => now(),
                ]);
                break;
        }
    }

    private function normalizePostmarkEvent(?string $type): string
    {
        return match ($type) {
            'Delivery' => 'delivered',
            'Bounce' => 'bounced',
            'SpamComplaint' => 'complained',
            default => strtolower((string) $type),
        };
    }

    private function normalizeMessageId(string $messageId): string
    {
        return trim($messageId, '<> ');
    }
}
