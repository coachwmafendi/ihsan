<?php

use App\Jobs\SendDonationReceipt;
use App\Mail\DonationReceipt;
use App\Models\Donation;
use Illuminate\Support\Facades\Mail;

it('does not send a receipt to a donor with a bounced email', function () {
    Mail::fake();

    $donation = Donation::factory()->create();
    $donation->donor->update(['email_bounced_at' => now()]);

    (new SendDonationReceipt($donation))->handle();

    Mail::assertNothingSent();
});

it('does not send a receipt to a donor who complained', function () {
    Mail::fake();

    $donation = Donation::factory()->create();
    $donation->donor->update(['email_complained_at' => now()]);

    (new SendDonationReceipt($donation))->handle();

    Mail::assertNothingSent();
});

it('sends a receipt to a donor who can receive emails', function () {
    Mail::fake();

    $donation = Donation::factory()->create();

    (new SendDonationReceipt($donation))->handle();

    Mail::assertSent(DonationReceipt::class);
});
