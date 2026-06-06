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
                <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-gray-700 dark:text-gray-300">ID</span>
                    <span>{$publicId}</span>
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        @click="
                            navigator.clipboard.writeText('{$publicId}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        class="inline-flex items-center justify-center p-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        title="Copy ID"
                    >
                        <svg
                            x-show="!copied"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="w-3.5 h-3.5 text-gray-400"
                        >
                            <path fill-rule="evenodd" d="M13.887 3.182c.396.04.734.308.88.68l.324.853.98-.37a.75.75 0 01.963.748l-.148.876.79.487a.75.75 0 01.382.858l-.27 1.042.54.784a.75.75 0 01-.382 1.212l-1.042.27.487.79a.75.75 0 01-.748.963l-.876-.148-.37.98a.75.75 0 01-1.283.043l-.59-.886-.886.59a.75.75 0 01-1.042-.27l-.487-.79-.79.487a.75.75 0 01-.858-.382l1.042-.27-.54-.784a.75.75 0 01.382-1.212l1.042-.27-.487-.79a.75.75 0 01.748-.963l.876.148.37-.98a.75.75 0 01.68-.88zm-7.364 7.364a.75.75 0 01-.827-1.233l3-2a.75.75 0 01.827 1.233l-3 2zm-1.5 2.5a.75.75 0 01-.827-1.233l4.5-3a.75.75 0 01.827 1.233l-4.5 3z" clip-rule="evenodd" />
                        </svg>
                        <svg
                            x-show="copied"
                            x-cloak
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="w-3.5 h-3.5 text-green-500"
                        >
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
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
