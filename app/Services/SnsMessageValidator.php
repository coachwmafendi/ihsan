<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SnsMessageValidator
{
    public function validate(array $message): bool
    {
        $signature = $message['Signature'] ?? '';
        $certUrl = $message['SigningCertURL'] ?? '';

        if (blank($signature) || blank($certUrl)) {
            return false;
        }

        if (! $this->isTrustedCertUrl($certUrl)) {
            return false;
        }

        $canonical = $this->canonicalString($message);

        try {
            $cert = Http::timeout(10)->connectTimeout(5)->get($certUrl)->body();
        } catch (\Exception $e) {
            Log::warning('Unable to fetch SNS signing certificate.', ['url' => $certUrl, 'error' => $e->getMessage()]);

            return false;
        }

        if (blank($cert)) {
            return false;
        }

        $publicKey = openssl_get_publickey($cert);

        if ($publicKey === false) {
            return false;
        }

        // SNS SignatureVersion 2 signs with SHA256; version 1 uses SHA1.
        $algorithm = ($message['SignatureVersion'] ?? '1') === '2'
            ? OPENSSL_ALGO_SHA256
            : OPENSSL_ALGO_SHA1;

        $verified = openssl_verify(
            $canonical,
            base64_decode((string) $signature),
            $publicKey,
            $algorithm
        ) === 1;

        openssl_free_key($publicKey);

        return $verified;
    }

    private function isTrustedCertUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        return str_ends_with($host, '.amazonaws.com') && str_starts_with($host, 'sns.');
    }

    private function canonicalString(array $message): string
    {
        $type = $message['Type'] ?? '';

        if ($type === 'Notification') {
            // AWS requires the fields in alphabetical order; Subject (when present)
            // sits between MessageId and Timestamp, not at the end.
            $parts = [
                'Message' => $message['Message'] ?? '',
                'MessageId' => $message['MessageId'] ?? '',
            ];

            if (isset($message['Subject'])) {
                $parts['Subject'] = $message['Subject'];
            }

            $parts['Timestamp'] = $message['Timestamp'] ?? '';
            $parts['TopicArn'] = $message['TopicArn'] ?? '';
            $parts['Type'] = $type;
        } else {
            // SubscriptionConfirmation or UnsubscribeConfirmation
            $parts = [
                'Message' => $message['Message'] ?? '',
                'MessageId' => $message['MessageId'] ?? '',
                'SubscribeURL' => $message['SubscribeURL'] ?? '',
                'Timestamp' => $message['Timestamp'] ?? '',
                'Token' => $message['Token'] ?? '',
                'TopicArn' => $message['TopicArn'] ?? '',
                'Type' => $type,
            ];
        }

        $lines = [];
        foreach ($parts as $key => $value) {
            $lines[] = $key;
            $lines[] = $value;
        }

        return implode("\n", $lines)."\n";
    }
}
