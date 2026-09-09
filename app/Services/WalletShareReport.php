<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * How many donations are finished with a wallet rather than a card.
 *
 * The monthly offer now stands between choosing an amount and reaching the
 * wallet button. That may raise the average gift, and it may cost some of the
 * fastest-converting donors we have - a wallet payment is two taps and no
 * form. Both are plausible and neither is settled by argument.
 */
class WalletShareReport
{
    /**
     * Below this a week is noise: one donor swings the percentage by tens of
     * points and the trend line means nothing.
     */
    public const MinimumWeeklyDonations = 5;

    private const WalletMethods = ['apple_pay', 'google_pay'];

    /**
     * @return array<int, array{week: string, donations: int, wallet: int, card: int, share: float|null, median: float, thin: bool}>
     */
    public function weekly(int $weeks = 8): array
    {
        $weeks = max(1, $weeks);
        $since = CarbonImmutable::now()->startOfWeek()->subWeeks($weeks - 1);

        return Donation::query()
            ->where('status', DonationStatus::Succeeded)
            ->where('created_at', '>=', $since)
            ->get(['created_at', 'payment_method_type', 'gross_amount', 'base_amount', 'currency'])
            ->groupBy(fn (Donation $donation) => CarbonImmutable::parse($donation->created_at)->startOfWeek()->format('Y-m-d'))
            ->sortKeys()
            ->map(fn (Collection $week, string $starting) => $this->summarise($week, $starting))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Donation>  $week
     * @return array{week: string, donations: int, wallet: int, card: int, share: float|null, median: float, thin: bool}
     */
    private function summarise(Collection $week, string $starting): array
    {
        $total = $week->count();
        $wallet = $week->filter(fn (Donation $d) => in_array($d->payment_method_type, self::WalletMethods, true))->count();

        // In ringgit, so a week of dollars does not read as a week of pennies.
        $amounts = $week
            ->map(fn (Donation $d) => (float) ($d->base_amount ?? $d->gross_amount))
            ->sort()
            ->values();

        return [
            'week' => $starting,
            'donations' => $total,
            'wallet' => $wallet,
            'card' => $total - $wallet,
            'share' => $total === 0 ? null : round($wallet / $total * 100, 1),
            'median' => $amounts->isEmpty() ? 0.0 : (float) $amounts[(int) floor(($amounts->count() - 1) / 2)],
            'thin' => $total < self::MinimumWeeklyDonations,
        ];
    }
}
