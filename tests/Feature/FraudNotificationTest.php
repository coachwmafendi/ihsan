<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Fraud\BlockedDonation;
use App\Models\Organization;
use App\Services\FraudDetectionService;
use Illuminate\Support\Facades\Mail;

it('sends fraud alert notification to admins', function () {
    Mail::fake();

    $organization = Organization::factory()->create([
        'contact_email' => 'admin@example.com',
    ]);
    $campaign = Campaign::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'risk_score' => 85,
        'fraud_status' => 'blocked',
    ]);

    FraudDetectionService::notifyAdmins(
        $donation,
        'Stripe Radar high risk score',
        'blocked'
    );

    Mail::assertQueued(\App\Mail\FraudAlertNotification::class, function ($mail) {
        return $mail->hasTo('admin@example.com');
    });
});
