<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DeploymentDriftAlert extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $deployed,
        public string $latest,
        public string $latestMessage,
        public Carbon $committedAt,
        public string $branch,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Production is behind '.$this->branch,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.deployment-drift-alert');
    }
}
