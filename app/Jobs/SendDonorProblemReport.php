<?php

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorProblemReportNotification;
use App\Mail\DonorProblemReportReceived;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * A problem reported from the donor portal produces two emails: an
 * acknowledgement to the donor, and the report itself to whoever runs the
 * platform. The organization's own admins are deliberately not copied — the
 * form asks about the donor portal, which is the platform's to fix.
 */
class SendDonorProblemReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donor $donor,
        public Organization $organization,
        public string $message,
    ) {}

    public function handle(): void
    {
        if ($this->organization->trashed()) {
            return;
        }

        $this->notifyDonor();
        $this->notifyPlatformAdmin();
    }

    /**
     * Tell the donor the message arrived, so they are not left guessing
     * whether the form worked.
     */
    private function notifyDonor(): void
    {
        if (blank($this->donor->email)) {
            return;
        }

        $messageId = Str::uuid()->toString();
        $mailable = new DonorProblemReportReceived(
            $this->donor,
            $this->organization,
            $this->message,
            $messageId,
        );

        app(LogDonorEmail::class)->handle(
            donor: $this->donor,
            mailable: $mailable,
            organization: $this->organization,
            messageId: $messageId,
        );

        Mail::to($this->donor->email)->queue($mailable);
    }

    private function notifyPlatformAdmin(): void
    {
        $adminEmail = config('app.admin_email');

        if (blank($adminEmail)) {
            // Without this the report is silently dropped, which is how the
            // old behaviour hid itself.
            Log::warning('Donor problem report has nowhere to go: app.admin_email is not set.', [
                'organization_id' => $this->organization->getKey(),
                'donor_id' => $this->donor->getKey(),
            ]);

            return;
        }

        Mail::to($adminEmail)->queue(
            new DonorProblemReportNotification($this->donor, $this->organization, $this->message)
        );
    }
}
