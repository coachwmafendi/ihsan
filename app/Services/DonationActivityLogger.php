<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Donation;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class DonationActivityLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function created(Donation $donation, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'donation.created',
            description: self::amountLabel($donation).' donation '.$donation->public_id.' created.',
            context: $context,
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function processingInitiated(Donation $donation, string $processor, string $transactionId, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'payment_processing_initiated',
            description: 'Processing for '.self::amountLabel($donation).' donation '.$donation->public_id.' initiated.',
            context: array_merge($context, [
                'processor' => $processor,
                'transaction_id' => $transactionId,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function transactionAttemptInitiated(Donation $donation, string $processor, string $transactionId, ?int $attempt = null, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'transaction_attempt_initiated',
            description: 'Transaction attempt initiated for '.self::amountLabel($donation).' donation '.$donation->public_id.'.',
            context: array_merge($context, [
                'processor' => $processor,
                'transaction_id' => $transactionId,
                'attempt' => $attempt,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function transactionAttemptSucceeded(Donation $donation, string $processor, string $transactionId, ?int $attempt = null, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'transaction_attempt_succeeded',
            description: 'Transaction attempt succeeded for '.self::amountLabel($donation).' donation '.$donation->public_id.'.',
            context: array_merge($context, [
                'processor' => $processor,
                'transaction_id' => $transactionId,
                'attempt' => $attempt,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function transactionAttemptFailed(Donation $donation, string $processor, string $transactionId, string $reason, ?int $attempt = null, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'transaction_attempt_failed',
            description: 'Transaction attempt failed for '.self::amountLabel($donation).' donation '.$donation->public_id.'.',
            context: array_merge($context, [
                'processor' => $processor,
                'transaction_id' => $transactionId,
                'attempt' => $attempt,
                'reason' => $reason,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function succeeded(Donation $donation, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'donation.succeeded',
            description: self::amountLabel($donation).' donation '.$donation->public_id.' succeeded.',
            context: array_merge($context, [
                'to_status' => $donation->status->value,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function failed(Donation $donation, string $reason, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'donation.failed',
            description: self::amountLabel($donation).' donation '.$donation->public_id.' failed.',
            context: array_merge($context, [
                'reason' => $reason,
                'to_status' => $donation->status->value,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function cancelled(Donation $donation, string $reason, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            donation: $donation,
            event: 'donation.cancelled',
            description: self::amountLabel($donation).' donation '.$donation->public_id.' cancelled.',
            context: array_merge($context, [
                'reason' => $reason,
                'to_status' => $donation->status->value,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function refunded(Donation $donation, float $amount, ?User $causer = null, array $context = []): Activity
    {
        $currency = strtoupper($donation->currency);

        return self::log(
            donation: $donation,
            event: 'donation.refunded',
            description: $currency.' '.number_format($amount, 2).' refunded for donation '.$donation->public_id.'.',
            context: array_merge($context, [
                'refund_amount' => $amount,
                'currency' => $donation->currency,
                'to_status' => $donation->status->value,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function log(Donation $donation, string $event, string $description, ?User $causer, array $context, array $extra = []): Activity
    {
        $properties = array_merge([
            'public_id' => $donation->public_id,
            'amount' => (float) $donation->gross_amount,
            'currency' => $donation->currency,
            'initiator' => $context['initiator'] ?? self::defaultInitiator($causer),
            'source' => $context['source'] ?? self::defaultSource($causer),
        ], $context, $extra);

        /** @var Activity $activity */
        $activity = activity()
            ->performedOn($donation)
            ->event($event)
            ->withProperties($properties)
            ->useLog('donation')
            ->log($description);

        if ($causer !== null) {
            $activity->causer()->associate($causer);
            $activity->save();
        }

        return $activity;
    }

    private static function amountLabel(Donation $donation): string
    {
        $currency = strtoupper($donation->currency);
        $amount = number_format((float) $donation->gross_amount, 2);

        return $currency.' '.$amount;
    }

    private static function defaultInitiator(?User $causer): string
    {
        if ($causer instanceof User) {
            return 'admin';
        }

        return 'system';
    }

    private static function defaultSource(?User $causer): string
    {
        if ($causer instanceof User) {
            return 'manual';
        }

        return 'system_automated';
    }
}
