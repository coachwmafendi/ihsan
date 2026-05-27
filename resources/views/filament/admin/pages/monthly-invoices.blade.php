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

        {{ $this->table }}
    </div>
</x-filament-panels::page>
