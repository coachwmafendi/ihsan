<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Figures shared by the weekly and monthly report emails.
 */
class DonationReportFigures
{
    /**
     * @return Builder<Donation>
     */
    public function succeededDonations(Organization $organization): Builder
    {
        return Donation::query()
            ->whereHas('campaign', fn (Builder $query) => $query->where('organization_id', $organization->getKey()))
            ->where('status', DonationStatus::Succeeded);
    }

    public function totalBetween(Organization $organization, CarbonInterface $startUtc, CarbonInterface $endUtc): float
    {
        return (float) $this->succeededDonations($organization)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->sum(Donation::reportAmountColumn());
    }

    /**
     * A total that mixes currencies is converted at each donation's settled
     * rate, so it is an estimate rather than a figure that reconciles to the cent.
     *
     * @param  Collection<int, Donation>  $donations
     */
    public function hasForeignCurrency(Collection $donations): bool
    {
        return $donations->contains(
            fn (Donation $donation): bool => strtolower($donation->currency ?? '') !== 'myr'
        );
    }

    /**
     * Percentage change from the previous period, or null when there was
     * nothing to compare with.
     */
    public function changePercent(float $total, float $previousTotal): ?float
    {
        if ($previousTotal <= 0) {
            return null;
        }

        return round(($total - $previousTotal) / $previousTotal * 100, 1);
    }

    /**
     * @return array{new_plan_count: int, cancelled_plan_count: int, past_due_plan_count: int}
     */
    public function planCounts(Organization $organization, CarbonInterface $startUtc, CarbonInterface $endUtc): array
    {
        $subscriptions = fn (): Builder => Subscription::query()
            ->whereHas('campaign', fn (Builder $query) => $query->where('organization_id', $organization->getKey()));

        return [
            'new_plan_count' => $subscriptions()
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->whereNotIn('status', [SubscriptionStatus::Incomplete, SubscriptionStatus::IncompleteExpired])
                ->count(),
            'cancelled_plan_count' => $subscriptions()
                ->whereBetween('cancelled_at', [$startUtc, $endUtc])
                ->count(),
            'past_due_plan_count' => $subscriptions()
                ->where('status', SubscriptionStatus::PastDue)
                ->count(),
        ];
    }
}
