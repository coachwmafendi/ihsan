<?php

namespace App\Filament\App\Resources\Donations\Pages;

use App\Filament\App\Resources\Donations\DonationResource;
use App\Models\Donation;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    protected string $view = 'filament.app.resources.donations.pages.list-donations';

    public string $dateRange = 'all';

    public function hasDonations(): bool
    {
        return Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', auth()->user()->organization_id))->exists();
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $dateStart = match ($this->dateRange) {
            'today' => today(),
            'yesterday' => today()->subDay(),
            '7_days' => Carbon::now()->subDays(7)->startOfDay(),
            '30_days' => Carbon::now()->subDays(30)->startOfDay(),
            '90_days' => Carbon::now()->subDays(90)->startOfDay(),
            'this_month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        if (in_array($this->dateRange, ['today', 'yesterday'], true)) {
            $query->whereDate('created_at', $dateStart);
        } elseif ($dateStart !== null) {
            $query->where('created_at', '>=', $dateStart);
        }

        return $query;

        if ($dateStart !== null) {
            $query->where('created_at', '>=', $dateStart);
        }

        return $query;
    }

    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;
    }

    public function getDateRangeLabel(): string
    {
        return match ($this->dateRange) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7_days' => 'Last 7 days',
            '30_days' => 'Last 30 days',
            '90_days' => 'Last 90 days',
            'this_month' => 'This month',
            default => 'All Time',
        };
    }
}
