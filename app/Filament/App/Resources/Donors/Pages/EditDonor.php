<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Enums\DonationStatus;
use App\Filament\App\Resources\Donors\DonorResource;
use App\Models\Donation;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class EditDonor extends ViewRecord
{
    protected static string $resource = DonorResource::class;

    protected string $view = 'filament.app.resources.donors.pages.edit-donor';

    public function hasFormWrapper(): bool
    {
        return false;
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * @return Collection<int, Donation>
     */
    public function getReceiptDonations(): Collection
    {
        return $this->record
            ->donations()
            ->whereHas('campaign', fn (Builder $query) => $query
                ->where('organization_id', auth()->user()->organization_id))
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('invoice_number')
            ->latest('created_at')
            ->get();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    $this->getFormContentComponent(),
                ]),
            ]);
    }

    public function getHeading(): string|Htmlable
    {
        $donor = $this->record;
        $organizationId = auth()->user()->organization_id;

        // Lifetime donated in MYR
        $lifetimeDonated = $donor->donations()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', 'succeeded')
            ->get()
            ->sum(function ($donation) {
                return $donation->base_amount ?? $donation->gross_amount;
            });

        $lifetimeFormatted = number_format($lifetimeDonated, 2);

        // Last donation date
        $lastDonation = $donor->donations()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', 'succeeded')
            ->latest('created_at')
            ->first();

        $lastDate = $lastDonation?->created_at?->format('d M Y') ?? '—';

        $publicId = $donor->public_id;

        return new HtmlString(<<<HTML
            <div class="space-y-1">
                <h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {$donor->name}
                </h1>
                <div class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
                    <span class="font-medium text-gray-700 dark:text-gray-300">ID</span>
                    <span>{$publicId}</span>
                    <x-ui.copy-button value="{$publicId}" />
                    <span class="mx-1">·</span>
                    <span>Lifetime donated</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">MYR {$lifetimeFormatted}</span>
                    <span class="mx-1">·</span>
                    <span>Last donation</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{$lastDate}</span>
                </div>
            </div>
        HTML);
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
