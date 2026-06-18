<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\DonorEmailLog;
use Illuminate\Mail\Events\MessageSent;

class RecordDonorEmailDelivery
{
    public function handle(MessageSent $event): void
    {
        $sent = $event->sent;
        $email = $sent->getOriginalMessage();

        if (! method_exists($email, 'getHeaders')) {
            return;
        }

        $messageId = $email->getHeaders()->getHeaderBody('X-Donor-Email-Log-Message-Id');

        if (blank($messageId)) {
            return;
        }

        $log = DonorEmailLog::query()
            ->where('message_id', $messageId)
            ->first();

        if ($log === null) {
            return;
        }

        $log->update([
            'delivery_status' => 'sent',
            'provider_message_id' => trim((string) $sent->getMessageId(), '<> '),
        ]);
    }
}
