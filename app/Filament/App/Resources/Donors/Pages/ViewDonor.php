<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Enums\DonationStatus;
use App\Filament\App\Resources\Donors\DonorResource;
use App\Models\Donation;
use Carbon\CarbonInterface;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class ViewDonor extends ViewRecord
{
    protected static string $resource = DonorResource::class;

    protected string $view = 'filament.app.resources.donors.pages.view-donor';

    public function getTitle(): string
    {
        return str($this->record->name)->title();
    }

    public function getSubheading(): string|Htmlable|null
    {
        $publicId = e($this->record->public_id ?? $this->record->getKey());
        $lifetime = $this->getLifetimeDonatedMyr();
        $last = $this->getLastDonationDate();
        $lastStr = $last ? $last->format('j M Y') : '—';

        $copyBtn = <<<HTML
            <button
                type="button"
                title="Copy ID"
                onclick="navigator.clipboard.writeText('{$publicId}').then(()=>{const t=this.querySelector('.icon-copy');const c=this.querySelector('.icon-check');t.classList.add('hidden');c.classList.remove('hidden');setTimeout(()=>{t.classList.remove('hidden');c.classList.add('hidden')},1500)})"
                class="inline-flex items-center align-middle text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
            >
                <svg class="icon-copy size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                </svg>
                <svg class="icon-check hidden size-3.5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </button>
        HTML;

        return new HtmlString(
            "ID {$publicId} {$copyBtn} · Lifetime donated {$lifetime} · Last donation {$lastStr}"
        );
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
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

    public function getLifetimeDonatedMyr(): string
    {
        $total = $this->record->donations()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->where('status', DonationStatus::Succeeded)
            ->get()
            ->sum(fn (Donation $d) => (float) ($d->base_amount ?? $d->gross_amount));

        return 'MYR '.number_format($total, 2);
    }

    public function getLastDonationDate(): ?CarbonInterface
    {
        return $this->record->donations()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->where('status', DonationStatus::Succeeded)
            ->latest()
            ->value('created_at');
    }

    private function scopeToCurrentOrganization(Builder $query): Builder
    {
        return $query->where('organization_id', auth()->user()->organization_id);
    }
}
