<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AuditLogQuery
{
    /**
     * Build an organization-scoped activity log query.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function forOrganization(Organization $organization, array $filters = []): Builder
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->where(function (Builder $q) use ($organization): void {
                $q->where(function (Builder $q2) use ($organization): void {
                    $q2->where('subject_type', Organization::class)
                        ->where('subject_id', $organization->getKey());
                })
                    ->orWhereHasMorph('subject', [Campaign::class, Element::class], function (Builder $q2) use ($organization): void {
                        $q2->where('organization_id', $organization->getKey());
                    })
                    ->orWhereHasMorph('subject', [Donation::class, Subscription::class], function (Builder $q2) use ($organization): void {
                        $q2->whereHas('campaign', function (Builder $cq) use ($organization): void {
                            $cq->where('organization_id', $organization->getKey());
                        });
                    });
            });

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['period']) && $filters['period'] !== 'all_time') {
            [$start, $end] = self::periodRange($filters['period']);

            if ($start !== null && $end !== null) {
                $query->whereBetween('activity_log.created_at', [$start, $end]);
            }
        }

        if (! empty($filters['initiator']) && $filters['initiator'] !== 'all') {
            self::applyInitiatorFilter($query, $filters['initiator']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.addcslashes($filters['search'], '%_\\').'%';

            $query->where(function (Builder $q) use ($search): void {
                $q->where('description', 'like', $search)
                    ->orWhere('log_name', 'like', $search)
                    ->orWhere('properties', 'like', $search)
                    ->orWhereHasMorph('causer', [User::class], function (Builder $q2) use ($search): void {
                        $q2->where('name', 'like', $search);
                    });
            });
        }

        return $query->orderByDesc('activity_log.created_at');
    }

    private static function applyInitiatorFilter(Builder $query, string $initiator): void
    {
        match ($initiator) {
            'system' => $query->where(function (Builder $q): void {
                $q->whereNull('causer_id')
                    ->where(function (Builder $q2): void {
                        $q2->whereNull('properties->initiator')
                            ->orWhere('properties->initiator', 'system');
                    });
            }),
            'admin' => $query->where('causer_type', User::class),
            'donor' => $query->where(function (Builder $q): void {
                $q->whereNull('causer_id')
                    ->where('properties->initiator', 'donor');
            }),
            default => null,
        };
    }

    /**
     * Filterable subject types shown in the audit log UI.
     *
     * @return array<class-string<Model>, string>
     */
    public static function subjectTypeOptions(): array
    {
        return [
            Organization::class => 'Organization',
            Campaign::class => 'Campaign',
            Element::class => 'Element',
            Donation::class => 'Donation',
            Subscription::class => 'Subscription',
        ];
    }

    /**
     * Common event filter options shown in the audit log UI.
     *
     * @return array<string, string>
     */
    public static function initiatorOptions(): array
    {
        return [
            'all' => 'All initiators',
            'system' => 'System',
            'admin' => 'Admin',
            'donor' => 'Donor',
        ];
    }

    public static function eventOptions(): array
    {
        return [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'donation.created' => 'Donation Created',
            'payment_processing_initiated' => 'Payment Processing Initiated',
            'transaction_attempt_initiated' => 'Transaction Attempt Initiated',
            'transaction_attempt_succeeded' => 'Transaction Attempt Succeeded',
            'transaction_attempt_failed' => 'Transaction Attempt Failed',
            'donation.succeeded' => 'Donation Succeeded',
            'donation.failed' => 'Donation Failed',
            'donation.cancelled' => 'Donation Cancelled',
            'donation.refunded' => 'Donation Refunded',
            'subscription.created' => 'Subscription Created',
            'subscription.updated' => 'Subscription Updated',
            'subscription.cancelled' => 'Subscription Cancelled',
            'subscription.paused' => 'Subscription Paused',
            'subscription.resumed' => 'Subscription Resumed',
            'installment.created' => 'Installment Created',
            'installment.charged' => 'Installment Charged',
            'installment.failed' => 'Installment Failed',
            'stripe_connected' => 'Stripe Connected',
            'stripe_disconnected' => 'Stripe Disconnected',
            'stripe_onboarding_completed' => 'Stripe Onboarding Completed',
        ];
    }

    /**
     * @return array{Carbon|null, Carbon|null}
     */
    private static function periodRange(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            '90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [null, null],
        };
    }
}
