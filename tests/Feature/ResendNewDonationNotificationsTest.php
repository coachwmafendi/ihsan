<?php

use App\Jobs\SendNewDonationNotification;
use App\Models\Donation;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

test('it queues a new donation notification for a specific donation', function () {
    Queue::fake();

    $donation = Donation::factory()->create();

    artisan('notifications:resend-new-donation', [
        '--donation' => $donation->public_id,
        '--force' => true,
    ])->assertSuccessful();

    Queue::assertPushed(SendNewDonationNotification::class, fn ($job) => $job->donation->is($donation));
});

test('it previews notifications without queuing during dry run', function () {
    Queue::fake();

    $donation = Donation::factory()->create();

    artisan('notifications:resend-new-donation', [
        '--donation' => $donation->public_id,
        '--dry-run' => true,
    ])->assertSuccessful();

    Queue::assertNothingPushed();
});

test('it fails when the specified donation is not found', function () {
    Queue::fake();

    artisan('notifications:resend-new-donation', [
        '--donation' => 'DUNKNOWN',
        '--force' => true,
    ])->assertFailed();

    Queue::assertNothingPushed();
});
