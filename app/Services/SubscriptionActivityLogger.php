<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Donation;
use App\Models\Subscription;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class SubscriptionActivityLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function created(Subscription $subscription, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'subscription.created',
            description: 'Recurring plan '.$subscription->public_id.' created.',
            context: $context,
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function updated(Subscription $subscription, string $changeSummary, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'subscription.updated',
            description: 'Recurring plan '.$subscription->public_id.' updated: '.$changeSummary.'.',
            context: $context,
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function cancelled(Subscription $subscription, string $reason, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'subscription.cancelled',
            description: 'Recurring plan '.$subscription->public_id.' cancelled.',
            context: array_merge($context, [
                'reason' => $reason,
                'to_status' => $subscription->status->value,
            ]),
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function paused(Subscription $subscription, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'subscription.paused',
            description: 'Recurring plan '.$subscription->public_id.' paused.',
            context: $context,
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function resumed(Subscription $subscription, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'subscription.resumed',
            description: 'Recurring plan '.$subscription->public_id.' resumed.',
            context: $context,
            causer: $causer,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function installmentCreated(Subscription $subscription, Donation $installment, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'installment.created',
            description: self::amountLabel($installment).' installment '.$installment->public_id.' of recurring plan '.$subscription->public_id.' created.',
            context: array_merge($context, [
                'installment_public_id' => $installment->public_id,
                'installment_id' => $installment->getKey(),
                'amount' => (float) $installment->gross_amount,
                'currency' => $installment->currency,
            ]),
            causer: $causer,
            subject: $installment,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function installmentCharged(Subscription $subscription, Donation $installment, string $processor, string $transactionId, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'installment.charged',
            description: self::amountLabel($installment).' installment '.$installment->public_id.' of recurring plan '.$subscription->public_id.' charged.',
            context: array_merge($context, [
                'installment_public_id' => $installment->public_id,
                'installment_id' => $installment->getKey(),
                'processor' => $processor,
                'transaction_id' => $transactionId,
                'amount' => (float) $installment->gross_amount,
                'currency' => $installment->currency,
                'to_status' => $installment->status->value,
            ]),
            causer: $causer,
            subject: $installment,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function installmentFailed(Subscription $subscription, Donation $installment, string $processor, string $transactionId, string $reason, ?User $causer = null, array $context = []): Activity
    {
        return self::log(
            subscription: $subscription,
            event: 'installment.failed',
            description: self::amountLabel($installment).' installment '.$installment->public_id.' of recurring plan '.$subscription->public_id.' failed.',
            context: array_merge($context, [
                'installment_public_id' => $installment->public_id,
                'installment_id' => $installment->getKey(),
                'processor' => $processor,
                'transaction_id' => $transactionId,
                'reason' => $reason,
                'amount' => (float) $installment->gross_amount,
                'currency' => $installment->currency,
                'to_status' => $installment->status->value,
            ]),
            causer: $causer,
            subject: $installment,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function log(Subscription $subscription, string $event, string $description, ?User $causer, array $context, ?Donation $subject = null, array $extra = []): Activity
    {
        $properties = array_merge([
            'public_id' => $subscription->public_id,
            'initiator' => $context['initiator'] ?? self::defaultInitiator($causer),
            'source' => $context['source'] ?? self::defaultSource($causer),
            'to_status' => $subscription->status->value,
        ], $context, $extra);

        $logSubject = $subject ?? $subscription;

        /** @var Activity $activity */
        $activity = activity()
            ->performedOn($logSubject)
            ->event($event)
            ->withProperties($properties)
            ->useLog('subscription')
            ->log($description);

        if ($causer !== null) {
            $activity->causer()->associate($causer);
            $activity->save();
        }

        return $activity;
    }

    private static function amountLabel(Donation $installment): string
    {
        $currency = strtoupper($installment->currency);
        $amount = number_format((float) $installment->gross_amount, 2);

        return $currency.' '.$amount;
    }

    private static function defaultInitiator(?User $causer): string
    {
        return $causer instanceof User ? 'admin' : 'system';
    }

    private static function defaultSource(?User $causer): string
    {
        return $causer instanceof User ? 'manual' : 'system_automated';
    }
}
