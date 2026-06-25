<?php

use App\Enums\DonationStatus;
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

test('it queues notifications for the latest N succeeded donations', function () {
    Queue::fake();

    $older = Donation::factory()->count(5)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 10.00,
        'created_at' => now()->subDays(7),
    ]);

    $newer = Donation::factory()->count(5)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 20.00,
        'created_at' => now(),
    ]);

    artisan('notifications:resend-new-donation', [
        '--latest' => 5,
        '--force' => true,
    ])->assertSuccessful();

    Queue::assertPushed(SendNewDonationNotification::class, 5);

    foreach ($newer as $donation) {
        Queue::assertPushed(SendNewDonationNotification::class, fn ($job) => $job->donation->is($donation));
    }

    foreach ($older as $donation) {
        Queue::assertNotPushed(SendNewDonationNotification::class, fn ($job) => $job->donation->is($donation));
    }
});

test('latest option supports dry run', function () {
    Queue::fake();

    Donation::factory()->count(5)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 10.00,
    ]);

    artisan('notifications:resend-new-donation', [
        '--latest' => 5,
        '--dry-run' => true,
    ])->assertSuccessful();

    Queue::assertNothingPushed();
});
