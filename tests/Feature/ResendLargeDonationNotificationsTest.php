<?php

use App\Jobs\SendLargeDonationNotification;
use App\Models\Donation;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

test('it queues a large donation notification for a specific donation', function () {
    Queue::fake();

    $donation = Donation::factory()->create();

    artisan('notifications:resend-large-donation', [
        '--donation' => $donation->public_id,
        '--force' => true,
    ])->assertSuccessful();

    Queue::assertPushed(SendLargeDonationNotification::class, fn ($job) => $job->donation->is($donation));
});

test('it previews notifications without queuing during dry run', function () {
    Queue::fake();

    $donation = Donation::factory()->create();

    artisan('notifications:resend-large-donation', [
        '--donation' => $donation->public_id,
        '--dry-run' => true,
    ])->assertSuccessful();

    Queue::assertNothingPushed();
});

test('it fails when the specified donation is not found', function () {
    Queue::fake();

    artisan('notifications:resend-large-donation', [
        '--donation' => 'DUNKNOWN',
        '--force' => true,
    ])->assertFailed();

    Queue::assertNothingPushed();
});

test('it targets donations within the last n days by default', function () {
    Queue::fake();

    $recent = Donation::factory()->create(['created_at' => now()->subDay()]);
    $old = Donation::factory()->create(['created_at' => now()->subDays(30)]);

    artisan('notifications:resend-large-donation', [
        '--days' => 7,
        '--force' => true,
    ])->assertSuccessful();

    Queue::assertPushed(SendLargeDonationNotification::class, fn ($job) => $job->donation->is($recent));
    Queue::assertNotPushed(SendLargeDonationNotification::class, fn ($job) => $job->donation->is($old));
});

test('it targets all succeeded donations when the all option is used', function () {
    Queue::fake();

    $recent = Donation::factory()->create(['created_at' => now()->subDay()]);
    $old = Donation::factory()->create(['created_at' => now()->subDays(30)]);

    artisan('notifications:resend-large-donation', [
        '--all' => true,
        '--force' => true,
    ])->assertSuccessful();

    Queue::assertPushed(SendLargeDonationNotification::class, fn ($job) => $job->donation->is($recent));
    Queue::assertPushed(SendLargeDonationNotification::class, fn ($job) => $job->donation->is($old));
});
