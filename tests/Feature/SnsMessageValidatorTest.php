<?php

declare(strict_types=1);

use App\Services\SnsMessageValidator;
use Illuminate\Support\Facades\Http;

function signSnsMessage(array $message, int $algorithm): array
{
    $keyPair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($keyPair, $privatePem);
    $publicPem = openssl_pkey_get_details($keyPair)['key'];

    // Rebuild the canonical string the validator signs (Notification form).
    $parts = [
        'Message' => $message['Message'] ?? '',
        'MessageId' => $message['MessageId'] ?? '',
        'Timestamp' => $message['Timestamp'] ?? '',
        'TopicArn' => $message['TopicArn'] ?? '',
        'Type' => $message['Type'] ?? '',
    ];

    $lines = [];
    foreach ($parts as $key => $value) {
        $lines[] = $key;
        $lines[] = $value;
    }
    $canonical = implode("\n", $lines)."\n";

    openssl_sign($canonical, $signature, $privatePem, $algorithm);

    $certUrl = 'https://sns.ap-southeast-1.amazonaws.com/SimpleNotificationService-test.pem';
    Http::fake([$certUrl => Http::response($publicPem, 200)]);

    return [$signature, $certUrl];
}

function baseNotification(): array
{
    return [
        'Type' => 'Notification',
        'MessageId' => 'msg-1',
        'TopicArn' => 'arn:aws:sns:ap-southeast-1:123:topic',
        'Message' => '{"eventType":"Delivery"}',
        'Timestamp' => '2026-07-22T05:00:00.000Z',
    ];
}

it('validates an sns message signed with sha256 (signature version 2)', function () {
    $message = baseNotification();
    $message['SignatureVersion'] = '2';

    [$signature, $certUrl] = signSnsMessage($message, OPENSSL_ALGO_SHA256);
    $message['Signature'] = base64_encode($signature);
    $message['SigningCertURL'] = $certUrl;

    expect(app(SnsMessageValidator::class)->validate($message))->toBeTrue();
});

it('validates an sns message signed with sha1 (signature version 1)', function () {
    $message = baseNotification();
    $message['SignatureVersion'] = '1';

    [$signature, $certUrl] = signSnsMessage($message, OPENSSL_ALGO_SHA1);
    $message['Signature'] = base64_encode($signature);
    $message['SigningCertURL'] = $certUrl;

    expect(app(SnsMessageValidator::class)->validate($message))->toBeTrue();
});

it('rejects a sha256 message when the signature does not match', function () {
    $message = baseNotification();
    $message['SignatureVersion'] = '2';

    [, $certUrl] = signSnsMessage($message, OPENSSL_ALGO_SHA256);
    $message['Signature'] = base64_encode('tampered');
    $message['SigningCertURL'] = $certUrl;

    expect(app(SnsMessageValidator::class)->validate($message))->toBeFalse();
});
