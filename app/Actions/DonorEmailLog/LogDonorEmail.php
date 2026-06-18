<?php

declare(strict_types=1);

namespace App\Actions\DonorEmailLog;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Mail\Mailable;

class LogDonorEmail
{
    public function handle(
        Donor $donor,
        Mailable $mailable,
        ?Organization $organization = null,
        ?Donation $donation = null,
        ?Subscription $subscription = null,
        ?DonorEmailLog $resentFrom = null,
        array $metadata = [],
    ): DonorEmailLog {
        $subject = $this->resolveSubject($mailable);

        return DonorEmailLog::create([
            'donor_id' => $donor->getKey(),
            'organization_id' => $organization?->getKey(),
            'donation_id' => $donation?->getKey(),
            'subscription_id' => $subscription?->getKey(),
            'resent_from_id' => $resentFrom?->getKey(),
            'mailable_class' => $mailable::class,
            'subject' => $subject,
            'metadata' => empty($metadata) ? null : $metadata,
            'sent_at' => now(),
        ]);
    }

    private function resolveSubject(Mailable $mailable): string
    {
        try {
            return $mailable->envelope()->subject ?? 'No subject';
        } catch (\Throwable $e) {
            report($e);

            return 'No subject';
        }
    }
}
