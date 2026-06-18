<?php

use App\Models\DonorEmailLog;
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
