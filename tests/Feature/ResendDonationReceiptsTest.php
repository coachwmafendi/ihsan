<?php

use App\Jobs\SendDonationReceipt;
use App\Models\Donation;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

test('it queues a receipt for a specific donation', function () {
    Queue::fake();

    $donation = Donation::factory()->create();

    artisan('donations:resend-receipts', [
        '--donation' => $donation->public_id,
        '--force' => true,
    ])->assertSuccessful();

    Queue::assertPushed(SendDonationReceipt::class, fn ($job) => $job->donation->is($donation));
});

test('it fails when the specified donation is not found', function () {
    Queue::fake();

    artisan('donations:resend-receipts', [
        '--donation' => 'DUNKNOWN',
        '--force' => true,
    ])->assertFailed();

    Queue::assertNothingPushed();
});

test('it previews receipts without queuing during dry run', function () {
    Queue::fake();

    $donation = Donation::factory()->create();

    artisan('donations:resend-receipts', [
        '--donation' => $donation->public_id,
        '--dry-run' => true,
    ])->assertSuccessful();

    Queue::assertNothingPushed();
});
