<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-warning-50 opacity-50 dark:bg-warning-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-document-text class="h-4 w-4 text-warning-500" />
                        Outstanding
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalOutstanding }}</div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Unpaid invoices</div>
                </div>
            </x-filament::section>

            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-success-50 opacity-50 dark:bg-success-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-check-circle class="h-4 w-4 text-success-500" />
                        Collected
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalCollected }}</div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Paid invoices</div>
                </div>
            </x-filament::section>

            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-info-50 opacity-50 dark:bg-info-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-paper-airplane class="h-4 w-4 text-info-500" />
                        Sent This Month
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $invoicesSent }}</div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Invoices generated</div>
                </div>
            </x-filament::section>
        </div>

        {{-- Collection Methods Context --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-swatch class="h-5 w-5 text-gray-400" />
                    Collection Methods
                </div>
            </x-slot>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    This page only shows organisations on <span class="font-medium text-gray-700 dark:text-gray-300">Monthly Invoice</span> billing.
                    Upfront deductions are collected automatically at the point of donation and do not generate invoices.
                </p>
                <div class="flex shrink-0 gap-2">
                    <div class="flex items-center gap-2 rounded-lg border border-info-200 bg-info-50/50 px-3 py-2 dark:border-info-800 dark:bg-info-900/10">
                        <x-heroicon-o-calendar class="h-4 w-4 text-info-500" />
                        <span class="text-xs font-semibold text-info-700 dark:text-info-400">Monthly Invoice</span>
                        <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $invoiceOrgs }}</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-lg border border-success-200 bg-success-50/50 px-3 py-2 dark:border-success-800 dark:bg-success-900/10">
                        <x-heroicon-o-bolt class="h-4 w-4 text-success-500" />
                        <span class="text-xs font-semibold text-success-700 dark:text-success-400">Upfront</span>
                        <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $upfrontOrgs }}</span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Upfront Deductions Quick Stat --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-bolt class="h-5 w-5 text-gray-400" />
                    Upfront Deductions This Month
                </div>
            </x-slot>

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total fees collected via upfront deduction</div>
                    <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">MYR {{ $totalUpfrontFees }}</div>
                </div>
                <a href="{{ route('filament.admin.pages.processing-fees') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    <x-heroicon-o-banknotes class="h-4 w-4" />
                    View Processing Fees
                </a>
            </div>
        </x-filament::section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
