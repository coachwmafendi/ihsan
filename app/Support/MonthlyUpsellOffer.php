<?php

namespace App\Support;

/**
 * A resolved monthly upsell offer, ready to hand to the donation form.
 */
final readonly class MonthlyUpsellOffer
{
    /**
     * @param  array<int, float>  $offers  The donor's own amount first, then an
     *                                     optional lighter alternative.
     * @param  string  $body  The message with the amount already substituted.
     * @param  array<int, string>  $bodySegments  The same message split on the
     *                                            amount, so the form can render
     *                                            it in bold without any markup
     *                                            passing through admin input.
     */
    public function __construct(
        public array $offers,
        public string $heading,
        public string $body,
        public array $bodySegments,
        public string $amountLabel,
        public string $declineLabel,
        public int $cooldownDays,
    ) {}

    /**
     * @return array{offers: array<int, float>, heading: string, body: string, bodySegments: array<int, string>, amountLabel: string, declineLabel: string, cooldownDays: int}
     */
    public function toArray(): array
    {
        return [
            'offers' => $this->offers,
            'heading' => $this->heading,
            'body' => $this->body,
            'bodySegments' => $this->bodySegments,
            'amountLabel' => $this->amountLabel,
            'declineLabel' => $this->declineLabel,
            'cooldownDays' => $this->cooldownDays,
        ];
    }
}
