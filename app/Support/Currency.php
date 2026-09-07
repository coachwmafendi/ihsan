<?php

namespace App\Support;

class Currency
{
    private static array $symbols = [
        'myr' => 'RM',
        'usd' => '$',
        'sgd' => 'S$',
    ];

    /**
     * Ringgit per unit of each currency, deliberately below where these pairs
     * trade. Everything the checkout has to state in the donor's currency but
     * knows only in ringgit - a fee, a minimum - is converted at these, because
     * the real rate is not known until Stripe settles the charge. Erring low
     * means an organisation is never left short by a ringgit that strengthened
     * between the quote and the charge.
     *
     * @var array<string, float>
     */
    private static array $floorRates = [
        'myr' => 1.00,
        'usd' => 4.00,
        'sgd' => 3.00,
    ];

    /**
     * Stripe's own minimum charge, expressed in the settlement currency it
     * actually applies to. A Malaysian account settles in ringgit, so a foreign
     * charge has to convert to at least RM2 - which is more than the SGD 0.50
     * Stripe lists for a Singaporean account, and exactly the USD 0.50 it lists
     * for an American one.
     *
     * @var array<string, float>
     */
    private const StripeMinimums = [
        'myr' => 2.00,
        'usd' => 0.50,
        'sgd' => 0.50,
    ];

    private const SettlementMinimumInRinggit = 2.00;

    public static function symbol(string $currency): string
    {
        return static::$symbols[strtolower($currency)] ?? strtoupper($currency);
    }

    /**
     * A ringgit figure written in the donor's currency, rounded up to five
     * cents so rounding never lands under the value it stands for.
     */
    public static function fromRinggit(float $ringgit, string $currency): float
    {
        $rate = static::$floorRates[strtolower($currency)] ?? 1.00;

        return ceil($ringgit / $rate * 20) / 20;
    }

    /**
     * The smallest amount Stripe will accept in this currency, taking both its
     * own floor and the ringgit our account settles in into account.
     */
    public static function chargeMinimum(string $currency): float
    {
        $currency = strtolower($currency);

        return max(
            self::StripeMinimums[$currency] ?? 0.50,
            static::fromRinggit(self::SettlementMinimumInRinggit, $currency),
        );
    }

    /**
     * What a campaign's own minimum - always set in ringgit - comes to for this
     * donor, never below what Stripe would accept anyway.
     */
    public static function minimumDonation(float $campaignMinimumInRinggit, string $currency): float
    {
        return max(
            static::fromRinggit($campaignMinimumInRinggit, $currency),
            static::chargeMinimum($currency),
        );
    }

    public static function format(string $currency, float $amount): string
    {
        return strtoupper($currency).' '.number_format($amount, 2);
    }

    /**
     * Format for display copy, dropping the cents on a whole amount so donors
     * read "RM 100" rather than "RM 100.00".
     */
    public static function formatCompact(string $currency, float $amount): string
    {
        return static::symbol($currency).' '.static::compactNumber($amount);
    }

    /**
     * The number on its own, without a currency symbol.
     */
    public static function compactNumber(float $amount): string
    {
        return number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }
}
