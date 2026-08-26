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
     */
    public function __construct(
        public array $offers,
        public string $heading,
        public string $body,
        public string $declineLabel,
        public int $cooldownDays,
    ) {}

    /**
     * @return array{offers: array<int, float>, heading: string, body: string, declineLabel: string, cooldownDays: int}
     */
    public function toArray(): array
    {
        return [
            'offers' => $this->offers,
            'heading' => $this->heading,
            'body' => $this->body,
            'declineLabel' => $this->declineLabel,
            'cooldownDays' => $this->cooldownDays,
        ];
    }
}
