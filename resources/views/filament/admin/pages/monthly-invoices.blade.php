<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Outstanding</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalOutstanding }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Unpaid invoices</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Collected</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalCollected }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Paid invoices</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Sent This Month</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $invoicesSent }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Invoices generated</div>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
