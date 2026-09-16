<?php

use App\Models\DonorEmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

it('reports a bounce ahead of anything else', function () {
    $log = DonorEmailLog::factory()->make([
        'delivery_status' => 'bounced',
        'sent_at' => now()->subDay(),
        'delivered_at' => null,
        'bounced_at' => now()->subHours(2),
        'bounce_reason' => 'Mailbox does not exist',
        'opened_at' => null,
    ]);

    expect($log->status_label)->toBe('Bounced')
        ->and($log->status_tone)->toBe('failed')
        ->and($log->status_detail)->toBe('Mailbox does not exist')
        ->and($log->status_at->timestamp)->toBe($log->bounced_at->timestamp);
});

it('falls back to a plain explanation when the bounce carries no reason', function () {
    $log = DonorEmailLog::factory()->make([
        'delivery_status' => 'bounced',
        'bounced_at' => now(),
        'bounce_reason' => null,
    ]);

    expect($log->status_detail)->toBe('The mail server rejected this address.');
});

it('reports a spam complaint ahead of an open', function () {
    $log = DonorEmailLog::factory()->make([
        'delivery_status' => 'complained',
        'complained_at' => now()->subHour(),
        'opened_at' => now()->subHours(3),
    ]);

    expect($log->status_label)->toBe('Marked spam')
        ->and($log->status_tone)->toBe('warning');
});

it('reports an open ahead of a delivery', function () {
    $log = DonorEmailLog::factory()->make([
        'delivery_status' => 'delivered',
        'delivered_at' => now()->subHours(4),
        'opened_at' => now()->subHour(),
        'bounced_at' => null,
        'complained_at' => null,
    ]);

    expect($log->status_label)->toBe('Opened')
        ->and($log->status_tone)->toBe('success')
        ->and($log->status_at->timestamp)->toBe($log->opened_at->timestamp);
});

it('reports a delivery that has not been opened', function () {
    $log = DonorEmailLog::factory()->make([
        'delivery_status' => 'delivered',
        'delivered_at' => now()->subHour(),
        'opened_at' => null,
        'bounced_at' => null,
        'complained_at' => null,
    ]);

    expect($log->status_label)->toBe('Delivered')
        ->and($log->status_tone)->toBe('info');
});

it('reports an email handed to the mail server but not yet confirmed', function () {
    $log = DonorEmailLog::factory()->make([
        'delivery_status' => 'sent',
        'sent_at' => now()->subHour(),
        'delivered_at' => null,
        'opened_at' => null,
        'bounced_at' => null,
        'complained_at' => null,
    ]);

    expect($log->status_label)->toBe('Sent')
        ->and($log->status_tone)->toBe('default')
        ->and($log->status_detail)->toBe('Handed to the mail server. No delivery confirmation yet.');
});

it('reports an email still waiting to go out', function () {
    $log = DonorEmailLog::factory()->make([
        'delivery_status' => 'queued',
        'sent_at' => null,
        'delivered_at' => null,
        'opened_at' => null,
        'bounced_at' => null,
        'complained_at' => null,
    ]);

    expect($log->status_label)->toBe('Queued')
        ->and($log->status_tone)->toBe('pending')
        ->and($log->status_at)->toBeNull();
});
