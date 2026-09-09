<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Donation;

/**
 * Why a payment did not go through, in words somebody can act on.
 *
 * Stripe's reason was already being stored on every failed donation, buried in
 * the fee-details blob and shown on no screen at all. Answering "why did this
 * fail?" meant opening a shell and asking Stripe for a record we already had.
 *
 * The bank's own message says what happened. It does not say what to do next,
 * and that differs sharply: some declines clear on a second attempt, some never
 * will, and one of them means the card should not be tried again at all.
 */
class PaymentFailureReason
{
    /**
     * @param  string|null  $code  Stripe's decline_code where there is one, else its error code
     */
    private function __construct(
        public readonly ?string $code,
        public readonly string $message,
    ) {}

    public static function for(Donation $donation): ?self
    {
        $error = $donation->stripe_fee_details['last_payment_error'] ?? null;

        if (! is_array($error)) {
            return null;
        }

        $message = trim((string) ($error['message'] ?? ''));
        $code = $error['decline_code'] ?? $error['code'] ?? null;

        if ($message === '' && blank($code)) {
            return null;
        }

        return new self(
            code: filled($code) ? (string) $code : null,
            message: $message !== '' ? $message : 'The payment was declined.',
        );
    }

    /**
     * The same thing, in words fit to send to the person it happened to.
     *
     * Stripe's message is written for whoever is integrating, not for whoever
     * was paying, and some of it is plainly so: one decline reached a donor as
     * "You can provide payment_method_data or a new PaymentMethod to attempt to
     * fulfil this PaymentIntent again."
     */
    public function donorMessage(): string
    {
        $ours = match ($this->code) {
            'insufficient_funds' => 'There was not enough in the account to cover it.',
            'transaction_not_allowed', 'card_not_supported' => 'The bank does not allow this kind of purchase on that card.',
            'currency_not_supported' => 'The card cannot be charged in this currency.',
            'expired_card' => 'The card has expired.',
            'incorrect_cvc', 'invalid_cvc' => 'The security code did not match the card.',
            'incorrect_number', 'invalid_number' => 'The card number was not accepted.',
            'incorrect_expiry', 'invalid_expiry_month', 'invalid_expiry_year' => 'The expiry date did not match the card.',
            'authentication_required', 'payment_intent_authentication_failure' => 'The bank asked for an extra confirmation step that was not completed.',
            'processing_error' => 'Something went wrong on the bank\'s side.',
            'card_velocity_exceeded' => 'The card has been used too many times in a short period.',
            'lost_card', 'stolen_card', 'pickup_card' => 'The bank would not accept this card.',
            'do_not_honor', 'generic_decline' => 'The bank declined the payment without giving a reason.',
            default => null,
        };

        if ($ours !== null) {
            return $ours;
        }

        return $this->readsLikeDeveloperText($this->message)
            ? 'The bank declined the payment.'
            : $this->message;
    }

    /**
     * Stripe's own vocabulary, leaking. Nothing with an API object or a
     * snake_case parameter in it was written for a donor to read.
     */
    private function readsLikeDeveloperText(string $message): bool
    {
        return preg_match('/\b(PaymentMethod|PaymentIntent|SetupIntent|[a-z]+_[a-z_]+)\b/', $message) === 1;
    }

    /**
     * What the donor should do about it. Silence where we have nothing useful
     * to add, rather than a line of filler under every failure.
     */
    public function advice(): ?string
    {
        return match ($this->code) {
            'insufficient_funds' => 'There was not enough in the account. The same card may work later, or another one now.',
            'transaction_not_allowed', 'card_not_supported' => 'The bank does not allow this kind of purchase on that card. Prepaid cards are often blocked for online payments from abroad, so a different card is usually the quickest way through.',
            'currency_not_supported' => 'The card cannot be charged in this currency. Giving in ringgit instead usually works.',
            'expired_card' => 'The card has expired.',
            'incorrect_cvc', 'invalid_cvc' => 'The security code did not match the card.',
            'incorrect_number', 'invalid_number' => 'The card number was not accepted.',
            'incorrect_expiry', 'invalid_expiry_month', 'invalid_expiry_year' => 'The expiry date did not match the card.',
            'authentication_required', 'payment_intent_authentication_failure' => 'The bank asked for a confirmation step that was not completed. Trying again and finishing the bank\'s check should work.',
            'processing_error' => 'Something went wrong on the bank\'s side. This one usually clears on a second attempt.',
            'card_velocity_exceeded' => 'The card has been used too many times in a short period. Waiting a while before trying again should clear it.',
            'lost_card', 'stolen_card', 'pickup_card' => 'The bank has reported this card lost or stolen. Do not ask the donor to try it again - they need to speak to their bank.',
            'do_not_honor', 'generic_decline' => 'The bank declined without giving a reason, which they will only explain to the cardholder. Another card usually works.',
            default => null,
        };
    }

    /**
     * What to do next, addressed to the donor rather than about them. The
     * panel's version tells an admin not to send someone back to a stolen
     * card; the donor's version cannot be phrased that way.
     */
    public function donorAdvice(): ?string
    {
        return match ($this->code) {
            'insufficient_funds' => 'The same card may work later, or another one now.',
            'transaction_not_allowed', 'card_not_supported' => 'Prepaid cards are often blocked for online payments from abroad, so another card is usually the quickest way through.',
            'currency_not_supported' => 'Giving in ringgit instead usually works.',
            'expired_card' => 'Another card will do it.',
            'incorrect_cvc', 'invalid_cvc' => 'It is the three digits on the back of the card.',
            'incorrect_number', 'invalid_number', 'incorrect_expiry', 'invalid_expiry_month', 'invalid_expiry_year' => 'Worth checking the details on the card itself.',
            'authentication_required', 'payment_intent_authentication_failure' => 'Trying again and finishing the bank\'s check should be enough.',
            'processing_error' => 'This one usually clears on a second attempt.',
            'card_velocity_exceeded' => 'Waiting a while before trying again should clear it.',
            'lost_card', 'stolen_card', 'pickup_card' => 'Your bank will be able to tell you why. Another card will work in the meantime.',
            'do_not_honor', 'generic_decline' => 'Banks only explain these to the cardholder, so another card is usually quicker than asking.',
            default => null,
        };
    }

    /**
     * Whether asking the donor to try the same card again is reasonable advice.
     */
    public function worthRetrying(): bool
    {
        return ! in_array($this->code, [
            'lost_card',
            'stolen_card',
            'pickup_card',
            'transaction_not_allowed',
            'card_not_supported',
            'expired_card',
        ], true);
    }

    /**
     * The raw code, written for reading rather than for grep.
     */
    public function label(): ?string
    {
        return $this->code === null ? null : str_replace('_', ' ', $this->code);
    }
}
