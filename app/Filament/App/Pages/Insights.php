<?php

namespace App\Filament\App\Pages;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Subscription;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class Insights extends Page
{
    protected string $view = 'filament.app.pages.insights';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Insights';

    protected static ?int $navigationSort = 10;

    public string $totalRaised = '0.00';

    public string $monthlyRecurringRevenue = '0.00';

    public int $activeRecurringDonors = 0;

    public string $oneTimeDonationsTotal = '0.00';

    public string $firstInstallmentsTotal = '0.00';

    public string $averageDonationAmount = '0.00';

    public int $successfulDonationsCount = 0;

    public int $totalDonationsCount = 0;

    public int $activeSubscriptionsCount = 0;

    public int $pastDueSubscriptionsCount = 0;

    public string $successRate = '0';

    public bool $orgHasBank = false;

    public bool $orgHasStripe = false;

    public bool $hasCampaigns = false;

    public bool $hasElements = false;

    public bool $hasDonations = false;

    /**
     * @var array<int, array{label: string, amount: string, height: int}>
     */
    public array $dailyRevenue = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $frequencyBreakdown = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $statusBreakdown = [];

    /**
     * @var array<int, array{donor: string, campaign: string, amount: string, type: string}>
     */
    public array $recentDonations = [];

    public function mount(): void
    {
        $campaignIds = Campaign::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->pluck('id');

        $successfulDonations = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded);

        $this->totalRaised = $this->formatMoney((float) (clone $successfulDonations)->sum('gross_amount'));

        $this->monthlyRecurringRevenue = number_format((float) Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Active)
            ->where('interval', 'monthly')
            ->sum('amount'), 2, '.', '');

        $this->activeRecurringDonors = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Active)
            ->distinct('donor_id')
            ->count('donor_id');

        $this->oneTimeDonationsTotal = $this->formatMoney((float) (clone $successfulDonations)
            ->where('type', DonationType::OneTime)
            ->sum('gross_amount'));

        $this->firstInstallmentsTotal = $this->formatMoney((float) (clone $successfulDonations)
            ->where('type', DonationType::Recurring)
            ->sum('gross_amount'));

        $this->successfulDonationsCount = (clone $successfulDonations)->count();

        $this->totalDonationsCount = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->count();

        $this->averageDonationAmount = $this->formatMoney($this->successfulDonationsCount === 0
            ? 0
            : (float) (clone $successfulDonations)->sum('gross_amount') / $this->successfulDonationsCount);

        $this->activeSubscriptionsCount = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $this->pastDueSubscriptionsCount = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::PastDue)
            ->count();

        $this->successRate = (string) ($this->totalDonationsCount === 0
            ? 0
            : round(($this->successfulDonationsCount / $this->totalDonationsCount) * 100));

        $this->dailyRevenue = $this->buildDailyRevenue((clone $successfulDonations)->get());
        $this->frequencyBreakdown = $this->buildFrequencyBreakdown($campaignIds->all());
        $this->statusBreakdown = $this->buildStatusBreakdown($campaignIds->all());
        $this->recentDonations = $this->buildRecentDonations($campaignIds->all());

        $org = auth()->user()->organization;
        $this->orgHasBank = $org !== null
            && filled($org->bank_name)
            && filled($org->bank_account_number)
            && filled($org->bank_account_holder_name);
        $this->orgHasStripe = $org !== null
            && filled($org->stripe_account_id)
            && $org->stripe_onboarded;
        $this->hasCampaigns = $campaignIds->isNotEmpty();
        $this->hasElements = Element::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->exists();
        $this->hasDonations = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->exists();
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @param  Collection<int, Donation>  $donations
     * @return array<int, array{label: string, amount: string, height: int}>
     */
    private function buildDailyRevenue(Collection $donations): array
    {
        $days = collect(range(6, 0))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString());

        $amounts = $days->mapWithKeys(fn (string $date) => [
            $date => (float) $donations
                ->filter(fn (Donation $donation) => $donation->created_at->toDateString() === $date)
                ->sum('gross_amount'),
        ]);

        $max = max($amounts->max(), 1);

        return $amounts
            ->map(fn (float $amount, string $date): array => [
                'label' => now()->parse($date)->format('M j'),
                'amount' => $this->formatMoney($amount),
                'height' => max(8, (int) round(($amount / $max) * 100)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{label: string, value: string}>
     */
    private function buildFrequencyBreakdown(array $campaignIds): array
    {
        $oneTimeTotal = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->where('type', DonationType::OneTime)
            ->sum('gross_amount');

        $recurringTotal = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->where('type', DonationType::Recurring)
            ->sum('gross_amount');

        return [
            ['label' => 'One-time', 'value' => 'MYR '.$this->formatMoney((float) $oneTimeTotal)],
            ['label' => 'Recurring', 'value' => 'MYR '.$this->formatMoney((float) $recurringTotal)],
        ];
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{label: string, value: string}>
     */
    private function buildStatusBreakdown(array $campaignIds): array
    {
        return collect(DonationStatus::cases())
            ->map(fn (DonationStatus $status): array => [
                'label' => str($status->value)->headline()->toString(),
                'value' => (string) Donation::query()
                    ->whereIn('campaign_id', $campaignIds)
                    ->where('status', $status)
                    ->count(),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{donor: string, campaign: string, amount: string, type: string}>
     */
    private function buildRecentDonations(array $campaignIds): array
    {
        return Donation::query()
            ->with(['campaign:id,title', 'donor:id,name'])
            ->whereIn('campaign_id', $campaignIds)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Donation $donation): array => [
                'donor' => $donation->donor->name,
                'campaign' => $donation->campaign->title,
                'amount' => 'MYR '.$this->formatMoney((float) $donation->gross_amount),
                'type' => str($donation->type->value)->headline()->toString(),
            ])
            ->all();
    }
}
