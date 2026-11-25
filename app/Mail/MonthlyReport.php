<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyReport extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{title: string, count: int, total: string, approximate: bool}>  $campaigns
     * @param  array{one_time_count?: int, one_time_total?: string, recurring_count?: int, recurring_total?: string, new_donor_count?: int, returning_donor_count?: int, average_amount?: string, largest_amount?: string, new_plan_count?: int, cancelled_plan_count?: int, past_due_plan_count?: int}  $highlights
     */
    public function __construct(
        public Organization $organization,
        public int $donationCount,
        public string $totalAmount,
        public array $campaigns,
        public string $period,
        public bool $hasApproximation = false,
        public ?string $previousTotalAmount = null,
        public ?float $changePercent = null,
        public array $highlights = [],
        public ?string $reportUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->organization->name.' — Donation report, '.$this->period,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly-report',
        );
    }
}
