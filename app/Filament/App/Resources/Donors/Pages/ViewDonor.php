<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Filament\App\Resources\Donors\DonorResource;
use App\Models\Donation;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ViewDonor extends ViewRecord
{
    protected static string $resource = DonorResource::class;

    protected string $view = 'filament.app.resources.donors.pages.view-donor';

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function hasDonationRecords(): bool
    {
        return $this->record->donations()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->exists();
    }

    public function hasRecurringPlans(): bool
    {
        return $this->record->subscriptions()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->exists();
    }

    public function hasReceiptRecords(): bool
    {
        return $this->getReceiptDonations()->isNotEmpty();
    }

    /**
     * @return Collection<int, Donation>
     */
    public function getReceiptDonations(): Collection
    {
        return $this->record->donations()
            ->whereNotNull('invoice_number')
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->latest()
            ->get();
    }

    private function scopeToCurrentOrganization(Builder $query): Builder
    {
        return $query->where('organization_id', auth()->user()->organization_id);
    }
}
