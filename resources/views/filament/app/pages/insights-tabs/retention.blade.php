<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Retention</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Donor loyalty and repeat giving</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-4">
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Donors</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['totalDonors'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Repeat Donors</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['repeatDonors'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Repeat Rate</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['repeatRate'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">New This Month</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['newThisMonth'] }}</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $retentionOverview['returningThisMonth'] }} returning</div>
        </x-filament::section>
    </div>
</div>
