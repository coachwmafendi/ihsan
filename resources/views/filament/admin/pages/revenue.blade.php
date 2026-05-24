<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total processing fees</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalProcessingFees }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">All time transferred</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Donation volume</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalDonationVolume }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $totalTransactions }} successful transactions</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Average fee per transaction</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $averageFeePerTransaction }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">3% processing fee</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Effective fee rate</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">3%</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Per donation</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                Revenue by NGO
            </x-slot>

            <div class="mt-2">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-sm font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th class="pb-3 pr-4">Organization</th>
                            <th class="pb-3 pr-4">Donations</th>
                            <th class="pb-3 pr-4">Volume</th>
                            <th class="pb-3">Processing fees</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($revenueByOrganization as $row)
                            <tr class="text-sm">
                                <td class="py-3 pr-4 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                                <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">{{ $row['donations'] }}</td>
                                <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">{{ $row['volume'] }}</td>
                                <td class="py-3 font-semibold text-gray-950 dark:text-white">{{ $row['fees'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No revenue data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
