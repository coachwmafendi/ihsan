<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
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
                    })
                    // A donor belongs to no single organization, so reach them
                    // through the donations they made to this one.
                    ->orWhereHasMorph('subject', [Donor::class], function (Builder $q2) use ($organization): void {
                        $q2->whereHas('donations.campaign', function (Builder $cq) use ($organization): void {
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
                        $q2->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    })
                    ->orWhereHasMorph('subject', [
                        Campaign::class,
                        Donation::class,
                        Donor::class,
                        Element::class,
                        Organization::class,
                        Subscription::class,
                    ], function (Builder $q2) use ($search): void {
                        $q2->where('public_id', 'like', $search);
                    });
            });
        }

        return $query->orderByDesc('activity_log.created_at');
    }

    /**
     * Every entry recorded against one record, newest first.
     *
     * Scoping is left to the caller: a record page has already decided the
     * viewer may see the record, and its own history says nothing more.
     */
    public static function forSubject(Model $subject): Builder
    {
        return Activity::query()
            ->with('causer')
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->orderByDesc('activity_log.created_at')
            ->orderByDesc('activity_log.id');
    }

    /**
     * A recurring plan's history together with its installment entries.
     *
     * Installments are logged against the installment donation rather than the
     * plan, so they only join the plan's timeline when someone asks for them.
     */
    public static function forSubjectWithInstallments(Subscription $subscription): Builder
    {
        return Activity::query()
            ->with('causer')
            ->where(function (Builder $query) use ($subscription): void {
                $query->where(function (Builder $q) use ($subscription): void {
                    $q->where('subject_type', $subscription->getMorphClass())
                        ->where('subject_id', $subscription->getKey());
                })->orWhere(function (Builder $q) use ($subscription): void {
                    $q->where('subject_type', (new Donation)->getMorphClass())
                        ->whereIn('subject_id', $subscription->donations()->select('id'));
                });
            })
            ->orderByDesc('activity_log.created_at')
            ->orderByDesc('activity_log.id');
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
            Donor::class => 'Supporter',
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
            'donor' => 'Supporter',
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
