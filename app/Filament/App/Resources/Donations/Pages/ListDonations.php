<?php

namespace App\Filament\App\Resources\Donations\Pages;

use App\Filament\App\Resources\Donations\DonationResource;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    public string $dateRange = 'all';

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

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.app.resources.donations.pages.period-filter'),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }
}
