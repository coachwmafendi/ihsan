<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class FeeDriftAlert extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, array{count: int, drift: float}>  $groups
     */
    public function __construct(
        public array $groups,
        public int $days,
        public float $threshold,
    ) {}

    public function build(): self
    {
        return $this->subject('Fee drift detected: Stripe is not charging what we quote')
            ->markdown('emails.fee-drift-alert', [
                'groups' => $this->groups,
                'days' => $this->days,
                'threshold' => $this->threshold,
            ]);
    }
}
