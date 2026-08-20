<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DonorEmailLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailWebhookService
{
    public function __construct(
        private ?SnsMessageValidator $snsValidator = null,
    ) {}

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

                $log->donor->markEmailValidated();
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

                if (! $log->donor->hasComplainedEmail()) {
                    $log->donor->markEmailComplained();
                }
                break;

            case 'open':
                $this->markOpened($log);
                $log->donor->markEmailValidated();
                break;

            case 'opened':
                $this->markOpened($log);
                $log->donor->markEmailValidated();
                break;
        }
    }

    private function markOpened(DonorEmailLog $log, ?string $timestamp = null, array $extraMetadata = []): void
    {
        $openedAt = filled($timestamp) ? CarbonImmutable::parse($timestamp) : now();

        $metadata = $log->metadata ?? [];
        $metadata['open_count'] = ($metadata['open_count'] ?? 0) + 1;
        $metadata['last_opened_at'] = $openedAt->toIso8601String();

        foreach ($extraMetadata as $key => $value) {
            if (filled($value)) {
                $metadata[$key] = $value;
            }
        }

        $log->update([
            'opened_at' => $openedAt,
            'metadata' => $metadata,
        ]);
    }

    private function normalizePostmarkEvent(?string $type): string
    {
        return match ($type) {
            'Delivery' => 'delivered',
            'Bounce' => 'bounced',
            'SpamComplaint' => 'complained',
            'Open' => 'open',
            default => strtolower((string) $type),
        };
    }

    public function processSes(Request $request, string $token): void
    {
        // SNS posts its JSON body with a text/plain content type, so the framework
        // does not parse it automatically — decode the raw body ourselves.
        $payload = $request->all();

        if (! isset($payload['Type']) && ! isset($payload['eventType'])) {
            $decoded = json_decode($request->getContent(), true);

            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (isset($payload['Type'])) {
            $this->processSesSnsMessage($payload, $token);

            return;
        }

        if (! $this->verifyDirectSesToken($token)) {
            abort(401, 'Invalid SES webhook token.');
        }

        $this->processSesEvent($payload);
    }

    private function processSesSnsMessage(array $payload, string $token): void
    {
        if (! $this->snsValidator()->validate($payload)) {
            abort(401, 'Invalid SNS signature.');
        }

        $type = $payload['Type'] ?? '';

        if (! $this->isAllowedSnsTopicArn($payload['TopicArn'] ?? '')) {
            abort(401, 'Invalid SNS topic ARN.');
        }

        if ($type === 'SubscriptionConfirmation') {
            $this->confirmSnsSubscription($payload);

            return;
        }

        if ($type === 'UnsubscribeConfirmation') {
            return;
        }

        if ($type !== 'Notification') {
            return;
        }

        $message = json_decode($payload['Message'] ?? '{}', true);

        if (! is_array($message)) {
            return;
        }

        $this->processSesEvent($message);
    }

    private function processSesEvent(array $event): void
    {
        $eventType = strtolower($event['eventType'] ?? $event['event_type'] ?? $event['EventType'] ?? '');
        $mail = $event['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;
        $headers = $mail['headers'] ?? [];

        $log = $this->findSesLog($messageId, $headers);

        if ($log === null) {
            Log::info('SES event received but no matching DonorEmailLog found.', ['message_id' => $messageId, 'event_type' => $eventType]);

            return;
        }

        switch ($eventType) {
            case 'delivery':
                $log->update([
                    'delivery_status' => 'delivered',
                    'delivered_at' => now(),
                ]);

                $log->donor->markEmailValidated();
                break;

            case 'bounce':
                $bounce = $event['bounce'] ?? [];
                $reason = $this->resolveBounceReason($bounce);
                $isPermanent = ($bounce['bounceType'] ?? '') === 'Permanent';

                $log->update([
                    'delivery_status' => 'bounced',
                    'bounced_at' => now(),
                    'bounce_reason' => $reason,
                ]);

                if ($isPermanent && ! $log->donor->hasBouncedEmail()) {
                    $log->donor->markEmailBounced();
                }
                break;

            case 'complaint':
                $log->update([
                    'delivery_status' => 'complained',
                    'complained_at' => now(),
                ]);

                if (! $log->donor->hasComplainedEmail()) {
                    $log->donor->markEmailComplained();
                }
                break;

            case 'open':
                $open = $event['open'] ?? [];

                $this->markOpened(
                    $log,
                    $open['timestamp'] ?? null,
                    [
                        'opened_ip' => $open['ipAddress'] ?? null,
                        'opened_user_agent' => $open['userAgent'] ?? null,
                    ],
                );

                $log->donor->markEmailValidated();
                break;
        }
    }

    private function findSesLog(?string $messageId, array $headers): ?DonorEmailLog
    {
        foreach ($headers as $header) {
            if (is_array($header) && ($header['name'] ?? '') === 'X-Donor-Email-Log-Message-Id' && filled($header['value'] ?? '')) {
                $log = DonorEmailLog::query()->where('message_id', $header['value'])->first();

                if ($log !== null) {
                    return $log;
                }
            }
        }

        if (filled($messageId)) {
            return DonorEmailLog::query()
                ->where('provider_message_id', $this->normalizeMessageId($messageId))
                ->orWhere('message_id', $this->normalizeMessageId($messageId))
                ->first();
        }

        return null;
    }

    private function resolveBounceReason(array $bounce): ?string
    {
        $recipients = $bounce['bouncedRecipients'] ?? [];
        $first = is_array($recipients) && ! empty($recipients) ? $recipients[0] : [];

        return $first['diagnosticCode']
            ?? $first['status']
            ?? $bounce['bounceSubType']
            ?? $bounce['bounceType']
            ?? null;
    }

    private function confirmSnsSubscription(array $payload): void
    {
        $url = $payload['SubscribeURL'] ?? '';

        if (blank($url)) {
            return;
        }

        Http::timeout(10)->connectTimeout(5)->get($url);
    }

    private function verifyDirectSesToken(string $token): bool
    {
        $expected = config('services.ses.webhook_token');

        return filled($expected) && hash_equals($expected, $token);
    }

    private function isAllowedSnsTopicArn(string $arn): bool
    {
        $expected = config('services.ses.topic_arn');

        if (blank($expected)) {
            return true;
        }

        return $arn === $expected;
    }

    private function snsValidator(): SnsMessageValidator
    {
        return $this->snsValidator ?? app(SnsMessageValidator::class);
    }

    private function normalizeMessageId(string $messageId): string
    {
        return trim($messageId, '<> ');
    }
}
