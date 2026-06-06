<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Filament\App\Resources\Donors\DonorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditDonor extends EditRecord
{
    protected static string $resource = DonorResource::class;

    protected string $view = 'filament.app.resources.donors.pages.edit-donor';

    public function hasFormWrapper(): bool
    {
        return false;
    }

    public function getHeading(): string | Htmlable
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
                    <button
                        type="button"
                        x-data
                        @click="
                            navigator.clipboard.writeText('{$publicId}');
                            \$el.querySelector('.copy-icon').classList.add('hidden');
                            \$el.querySelector('.check-icon').classList.remove('hidden');
                            setTimeout(() => {
                                \$el.querySelector('.copy-icon').classList.remove('hidden');
                                \$el.querySelector('.check-icon').classList.add('hidden');
                            }, 2000);
                        "
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-400"
                        title="Copy ID"
                    >
                        <svg class="copy-icon w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <svg class="check-icon hidden w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span class="copy-text">Copy</span>
                        <span class="check-text hidden text-green-600">Copied!</span>
                    </button>
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
