<div class="space-y-5 text-sm text-gray-700 dark:text-gray-300">
    <p>
        Upgrade links direct to pages where supporters can increase their recurring plan amount or cover transaction costs.
    </p>

    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
        Sharing these links with supporters can lead to a 35–65% increase in recurring donation revenue. Upgrade links are unique to each recurring plan, but can be accessed by anyone. Ensure these links below are specifically sent to the supporter with the email
        <span class="font-semibold">{{ $donor?->email ?? '—' }}</span>.
    </div>

    <div class="space-y-3">
        <div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Recurring amount upgrade link</p>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Leads to a page offering to increase the recurring plan amount.</p>
        </div>

        <div
            x-data="{ copied: false }"
            class="flex items-center gap-2"
        >
            <input
                type="text"
                readonly
                value="{{ $upgradeLink ?? '' }}"
                class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            />
            <button
                type="button"
                x-on:click="
                    navigator.clipboard.writeText('{{ addslashes($upgradeLink ?? '') }}');
                    copied = true;
                    setTimeout(() => copied = false, 1500);
                "
                class="shrink-0 rounded-lg border border-gray-300 bg-white p-2 text-gray-500 transition hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                title="Copy link"
            >
                <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                </svg>
                <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-success-600 dark:text-success-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </button>
        </div>
    </div>
</div>
