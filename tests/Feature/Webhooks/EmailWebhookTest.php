<?php

use App\Models\DonorEmailLog;
use App\Services\EmailWebhookService;
use App\Services\SnsMessageValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('marks an email as delivered via mailgun webhook', function () {
    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'provider-msg-123@example.com',
        'delivery_status' => 'sent',
    ]);

    $this->postJson(route('webhooks.mailgun'), [
        'signature' => ['timestamp' => '123', 'token' => 'abc', 'signature' => 'ignored_without_secret'],
        'event-data' => [
            'event' => 'delivered',
            'recipient' => $log->donor->email,
            'message' => [
                'headers' => [
                    'message-id' => 'provider-msg-123@example.com',
                ],
            ],
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('delivered')
        ->delivered_at->not->toBeNull();
});

it('marks an email as opened via mailgun webhook', function () {
    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'provider-open-123@example.com',
        'delivery_status' => 'delivered',
        'opened_at' => null,
    ]);

    $this->postJson(route('webhooks.mailgun'), [
        'signature' => ['timestamp' => '123', 'token' => 'abc', 'signature' => 'ignored_without_secret'],
        'event-data' => [
            'event' => 'opened',
            'recipient' => $log->donor->email,
            'message' => [
                'headers' => [
                    'message-id' => 'provider-open-123@example.com',
                ],
            ],
        ],
    ])->assertOk();

    expect($log->fresh())
        ->opened_at->not->toBeNull()
        ->metadata->open_count->toBe(1);
});

it('marks an email as bounced via mailgun webhook', function () {
    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'provider-bounce@example.com',
        'delivery_status' => 'sent',
    ]);

    $this->postJson(route('webhooks.mailgun'), [
        'signature' => ['timestamp' => '123', 'token' => 'abc', 'signature' => 'ignored_without_secret'],
        'event-data' => [
            'event' => 'failed',
            'severity' => 'permanent',
            'recipient' => $log->donor->email,
            'delivery-status' => [
                'message' => 'Mailbox does not exist',
            ],
            'message' => [
                'headers' => [
                    'message-id' => 'provider-bounce@example.com',
                ],
            ],
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('bounced')
        ->bounced_at->not->toBeNull()
        ->bounce_reason->toBe('Mailbox does not exist');
});

it('rejects mailgun webhook with invalid signature', function () {
    config(['services.mailgun.secret' => 'secret-key']);

    $this->postJson(route('webhooks.mailgun'), [
        'signature' => ['timestamp' => '123', 'token' => 'abc', 'signature' => 'bad'],
        'event-data' => ['event' => 'delivered'],
    ])->assertUnauthorized();
});

it('marks an email as delivered via postmark webhook using metadata', function () {
    $log = DonorEmailLog::factory()->create([
        'message_id' => Str::uuid()->toString(),
        'delivery_status' => 'sent',
    ]);

    config(['services.postmark.webhook_secret' => 'pm-secret']);

    $this->postJson(route('webhooks.postmark').'?secret=pm-secret', [
        'RecordType' => 'Delivery',
        'MessageID' => 'provider-postmark-123',
        'DeliveredAt' => now()->toIso8601String(),
        'Recipient' => $log->donor->email,
        'Metadata' => [
            'donor_email_log_message_id' => $log->message_id,
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('delivered')
        ->delivered_at->not->toBeNull();
});

it('marks an email as complained via postmark webhook', function () {
    $log = DonorEmailLog::factory()->create([
        'message_id' => Str::uuid()->toString(),
        'delivery_status' => 'delivered',
    ]);

    config(['services.postmark.webhook_secret' => 'pm-secret']);

    $this->postJson(route('webhooks.postmark').'?secret=pm-secret', [
        'RecordType' => 'SpamComplaint',
        'MessageID' => 'provider-postmark-456',
        'BouncedAt' => now()->toIso8601String(),
        'Recipient' => $log->donor->email,
        'Metadata' => [
            'donor_email_log_message_id' => $log->message_id,
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('complained')
        ->complained_at->not->toBeNull();
});

it('rejects postmark webhook with invalid secret', function () {
    config(['services.postmark.webhook_secret' => 'pm-secret']);

    $this->postJson(route('webhooks.postmark').'?secret=wrong', [
        'RecordType' => 'Delivery',
    ])->assertUnauthorized();
});

it('marks an email as opened via postmark webhook', function () {
    $log = DonorEmailLog::factory()->create([
        'message_id' => Str::uuid()->toString(),
        'delivery_status' => 'delivered',
        'opened_at' => null,
    ]);

    config(['services.postmark.webhook_secret' => 'pm-secret']);

    $this->postJson(route('webhooks.postmark').'?secret=pm-secret', [
        'RecordType' => 'Open',
        'MessageID' => 'provider-postmark-open-123',
        'ReceivedAt' => now()->toIso8601String(),
        'Recipient' => $log->donor->email,
        'Metadata' => [
            'donor_email_log_message_id' => $log->message_id,
        ],
    ])->assertOk();

    expect($log->fresh())
        ->opened_at->not->toBeNull()
        ->metadata->open_count->toBe(1);
});

it('marks an email as delivered via direct ses event', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'ses-message-id-123',
        'delivery_status' => 'sent',
    ]);

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'eventType' => 'Delivery',
        'mail' => [
            'messageId' => 'ses-message-id-123',
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('delivered')
        ->delivered_at->not->toBeNull();
});

it('parses ses events posted as text/plain like SNS does', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'ses-textplain-123',
        'delivery_status' => 'sent',
    ]);

    $body = json_encode([
        'eventType' => 'Delivery',
        'mail' => ['messageId' => 'ses-textplain-123'],
    ]);

    $this->call(
        'POST',
        route('webhooks.ses', ['token' => 'ses-secret-token']),
        [], [], [],
        ['CONTENT_TYPE' => 'text/plain; charset=UTF-8'],
        $body,
    )->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('delivered')
        ->delivered_at->not->toBeNull();
});

it('marks an email as bounced via direct ses event and flags permanent bounce', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'ses-bounce-id-123',
        'delivery_status' => 'sent',
    ]);

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'eventType' => 'Bounce',
        'bounce' => [
            'bounceType' => 'Permanent',
            'bouncedRecipients' => [
                ['diagnosticCode' => 'Mailbox does not exist'],
            ],
        ],
        'mail' => [
            'messageId' => 'ses-bounce-id-123',
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('bounced')
        ->bounced_at->not->toBeNull()
        ->bounce_reason->toBe('Mailbox does not exist');

    expect($log->donor->fresh()->hasBouncedEmail())->toBeTrue();
});

it('does not flag donor on transient ses bounce', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'ses-transient-id-123',
        'delivery_status' => 'sent',
    ]);

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'eventType' => 'Bounce',
        'bounce' => [
            'bounceType' => 'Transient',
            'bouncedRecipients' => [
                ['diagnosticCode' => 'Mailbox full'],
            ],
        ],
        'mail' => [
            'messageId' => 'ses-transient-id-123',
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('bounced');

    expect($log->donor->fresh()->hasBouncedEmail())->toBeFalse();
});

it('suppresses a donor on a ses complaint event', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'ses-complaint-id-123',
        'delivery_status' => 'sent',
    ]);

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'eventType' => 'Complaint',
        'complaint' => [
            'complaintFeedbackType' => 'abuse',
        ],
        'mail' => [
            'messageId' => 'ses-complaint-id-123',
        ],
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('complained')
        ->complained_at->not->toBeNull();

    expect($log->donor->fresh())
        ->hasComplainedEmail()->toBeTrue()
        ->canReceiveEmails()->toBeFalse();
});

it('rejects direct ses event with invalid token', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $this->postJson(route('webhooks.ses', ['token' => 'wrong-token']), [
        'eventType' => 'Delivery',
        'mail' => ['messageId' => 'ses-123'],
    ])->assertUnauthorized();
});

it('marks an email as opened via direct ses open event', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'ses-open-id-123',
        'delivery_status' => 'delivered',
        'opened_at' => null,
    ]);

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'eventType' => 'Open',
        'mail' => [
            'messageId' => 'ses-open-id-123',
        ],
        'open' => [
            'timestamp' => '2026-06-21T08:30:00.000Z',
            'ipAddress' => '203.0.113.42',
            'userAgent' => 'Mozilla/5.0',
        ],
    ])->assertOk();

    $freshLog = $log->fresh();

    expect($freshLog)
        ->opened_at->not->toBeNull()
        ->opened_at->format('Y-m-d H:i')->toBe('2026-06-21 08:30')
        ->metadata->open_count->toBe(1)
        ->metadata->opened_ip->toBe('203.0.113.42')
        ->metadata->opened_user_agent->toBe('Mozilla/5.0');
});

it('increments open count on repeated ses open events', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'ses-open-repeat-123',
        'delivery_status' => 'delivered',
        'opened_at' => '2026-06-20 10:00:00',
        'metadata' => ['open_count' => 1, 'last_opened_at' => '2026-06-20T10:00:00Z'],
    ]);

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'eventType' => 'Open',
        'mail' => [
            'messageId' => 'ses-open-repeat-123',
        ],
        'open' => [
            'timestamp' => '2026-06-21T08:30:00.000Z',
        ],
    ])->assertOk();

    expect($log->fresh())
        ->metadata->open_count->toBe(2);
});

it('marks an email as opened via sns wrapped ses open event', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'sns-ses-open-123',
        'delivery_status' => 'delivered',
        'opened_at' => null,
    ]);

    swapSesSnsValidatorToAlwaysPass();

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'Type' => 'Notification',
        'MessageId' => 'sns-message-id-open',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:ses-events',
        'Message' => json_encode([
            'eventType' => 'Open',
            'mail' => [
                'messageId' => 'sns-ses-open-123',
            ],
            'open' => [
                'timestamp' => '2026-06-21T09:15:00.000Z',
                'ipAddress' => '198.51.100.10',
                'userAgent' => 'Apple Mail',
            ],
        ]),
        'Timestamp' => now()->toIso8601String(),
        'SignatureVersion' => '1',
        'Signature' => 'dummy',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-123.pem',
    ])->assertOk();

    $freshLog = $log->fresh();

    expect($freshLog)
        ->opened_at->not->toBeNull()
        ->opened_at->format('Y-m-d H:i')->toBe('2026-06-21 09:15')
        ->metadata->open_count->toBe(1)
        ->metadata->opened_ip->toBe('198.51.100.10');
});

it('marks an email as delivered via sns wrapped ses event', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    $log = DonorEmailLog::factory()->create([
        'provider_message_id' => 'sns-ses-msg-123',
        'delivery_status' => 'sent',
    ]);

    swapSesSnsValidatorToAlwaysPass();

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'Type' => 'Notification',
        'MessageId' => 'sns-message-id-123',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:ses-events',
        'Message' => json_encode([
            'eventType' => 'Delivery',
            'mail' => [
                'messageId' => 'sns-ses-msg-123',
            ],
        ]),
        'Timestamp' => now()->toIso8601String(),
        'SignatureVersion' => '1',
        'Signature' => 'dummy',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-123.pem',
    ])->assertOk();

    expect($log->fresh())
        ->delivery_status->toBe('delivered')
        ->delivered_at->not->toBeNull();
});

it('confirms sns subscription via ses webhook', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    Http::fake();

    swapSesSnsValidatorToAlwaysPass();

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'Type' => 'SubscriptionConfirmation',
        'MessageId' => 'sns-sub-123',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:ses-events',
        'SubscribeURL' => 'https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription&Token=abc',
        'Token' => 'abc',
        'Timestamp' => now()->toIso8601String(),
        'SignatureVersion' => '1',
        'Signature' => 'dummy',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-123.pem',
    ])->assertOk();

    Http::assertSent(fn ($request) => $request->url() === 'https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription&Token=abc');
});

it('rejects sns message with invalid signature', function () {
    config(['services.ses.webhook_token' => 'ses-secret-token']);

    Http::fake([
        '*' => Http::response('invalid certificate content'),
    ]);

    $this->postJson(route('webhooks.ses', ['token' => 'ses-secret-token']), [
        'Type' => 'Notification',
        'MessageId' => 'sns-message-id-123',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:ses-events',
        'Message' => json_encode(['eventType' => 'Delivery']),
        'Timestamp' => now()->toIso8601String(),
        'SignatureVersion' => '1',
        'Signature' => 'dummy',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-123.pem',
    ])->assertUnauthorized();
})->skip(fn () => ! extension_loaded('openssl'), 'OpenSSL extension required for SNS validation.');

function swapSesSnsValidatorToAlwaysPass(): void
{
    $validator = new class extends SnsMessageValidator
    {
        public function validate(array $message): bool
        {
            return true;
        }
    };

    app()->instance(EmailWebhookService::class, new EmailWebhookService($validator));
}
