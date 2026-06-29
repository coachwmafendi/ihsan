<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Chip\FinalizeDonation;
use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessChipWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(FinalizeDonation $finalize): void
    {
        $purchaseId = $this->payload['id'] ?? null;

        if (! is_string($purchaseId) || blank($purchaseId)) {
            return;
        }

        $donation = Donation::where('chip_purchase_id', $purchaseId)->first();

        if ($donation === null) {
            return;
        }

        try {
            $finalize->finalize($donation);
        } catch (\RuntimeException $e) {
            // Purchase may still be pending; report but allow retry.
            report($e);

            $this->release(30);
        }
    }
}
