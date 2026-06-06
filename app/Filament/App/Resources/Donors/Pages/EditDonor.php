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
                        onclick="
                            navigator.clipboard.writeText('{$publicId}');
                            const btn = this;
                            const original = btn.innerHTML;
                            btn.innerHTML = '<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 20 20\" fill=\"currentColor\" class=\"w-3.5 h-3.5 text-green-500\"><path fill-rule=\"evenodd\" d=\"M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z\" clip-rule=\"evenodd\" /></svg>';
                            setTimeout(() => btn.innerHTML = original, 2000);
                        "
                        class="inline-flex items-center justify-center p-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                        title="Copy ID"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-gray-400">
                            <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.879a1.5 1.5 0 01-.44-1.06L9.62 7.5H8.5A1.5 1.5 0 017 6.5v-3zM5.5 7h1.75l1.75 1.75V12.5a1.5 1.5 0 01-1.5 1.5h-1A1.5 1.5 0 015 12.5v-4A1.5 1.5 0 015.5 7z" />
                            <path d="M4.5 15.75a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V13h-1v2.75a.5.5 0 01-.5.5h-6a.5.5 0 01-.5-.5v-6a.5.5 0 01.5-.5H7v-1H5.5a1.5 1.5 0 00-1.5 1.5v6z" />
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
