<x-filament::page>
    @php
        $org = auth()->user()->organization;
        $account = $this->stripeAccount();
    @endphp

    <x-filament::section icon="heroicon-o-currency-dollar">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span>Sambungan Stripe Connect</span>
                @if ($org && $org->stripe_onboarded)
                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-400/10 dark:text-teal-400">
                        <x-heroicon-o-check-circle class="size-3.5" />
                        Berjaya disambung
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                        <x-heroicon-o-exclamation-circle class="size-3.5" />
                        Belum selesai
                    </span>
                @endif
            </div>
        </x-slot>

        <div class="space-y-4">
            @if ($org && $org->stripe_account_id)
                <div class="flex items-center justify-end gap-2 text-xs">
                    <div class="font-medium text-gray-700 dark:text-gray-300">{{ $org->name }}</div>
                    <span class="rounded-md bg-gray-100 px-2.5 py-1 font-mono text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ $org->stripe_account_id }}
                    </span>
                </div>
            @endif

            @if ($account)
                <hr class="border-gray-200 dark:border-gray-700">

                <div>
                    <p class="mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">Account Details</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Charges</p>
                            <p class="mt-0.5 text-sm font-semibold {{ $account->charges_enabled ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $account->charges_enabled ? 'Enabled' : 'Disabled' }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Payouts</p>
                            <p class="mt-0.5 text-sm font-semibold {{ $account->payouts_enabled ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $account->payouts_enabled ? 'Enabled' : 'Disabled' }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Onboarding</p>
                            <p class="mt-0.5 text-sm font-semibold {{ $account->details_submitted ? 'text-teal-600 dark:text-teal-400' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ $account->details_submitted ? 'Completed' : 'Incomplete' }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Default Currency</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase">
                                {{ $account->default_currency ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sambung Semula</p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                            Putuskan sambungan Stripe Connect semasa dan sambung semula dengan akaun lain.
                        </p>
                    </div>
                    {{ $this->reconnectAction }}
                </div>
            @endif
        </div>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-banknotes">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span>Mata Wang Diterima</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pilih mata wang yang boleh digunakan penderma untuk membuat derma.
                Ringgit Malaysia (MYR) sentiasa diaktifkan.
            </p>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <input
                            type="checkbox"
                            wire:model.live="currencies.myr"
                            checked
                            disabled
                            class="size-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-600 dark:bg-gray-800"
                        />
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">MYR</p>
                            <p class="text-xs text-gray-500">Ringgit Malaysia</p>
                        </div>
                    </label>
                </div>

                <div>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <input
                            type="checkbox"
                            wire:model.live="currencies.usd"
                            class="size-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-600 dark:bg-gray-800"
                        />
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">USD</p>
                            <p class="text-xs text-gray-500">US Dollar</p>
                        </div>
                    </label>
                </div>

                <div>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <input
                            type="checkbox"
                            wire:model.live="currencies.sgd"
                            class="size-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-600 dark:bg-gray-800"
                        />
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">SGD</p>
                            <p class="text-xs text-gray-500">Singapore Dollar</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament::page>
