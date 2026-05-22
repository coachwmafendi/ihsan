<x-filament::page>
    <x-filament::section heading="Stripe" icon="heroicon-o-currency-dollar">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                    @php
                        $org = auth()->user()->organization;
                    @endphp
                    @if ($org && $org->stripe_onboarded)
                        <p class="mt-1 text-sm font-semibold text-teal-600 dark:text-teal-400">
                            <x-heroicon-o-check-circle class="inline size-4" />
                            Berjaya disambung
                        </p>
                    @else
                        <p class="mt-1 text-sm font-semibold text-amber-600 dark:text-amber-400">
                            <x-heroicon-o-exclamation-circle class="inline size-4" />
                            Belum selesai
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($org && $org->stripe_account_id)
                        <div class="text-right text-xs">
                            <div class="font-medium text-gray-700 dark:text-gray-300">{{ $org->name }}</div>
                            <span class="rounded-md bg-gray-100 px-2.5 py-1 font-mono text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ $org->stripe_account_id }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sambung Semula</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        Putuskan sambungan Stripe semasa dan sambung semula dengan akaun lain.
                    </p>
                </div>
                {{ $this->reconnectAction }}
            </div>
        </div>
    </x-filament::section>
</x-filament::page>
