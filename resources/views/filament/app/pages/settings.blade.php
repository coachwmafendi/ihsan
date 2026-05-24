<x-filament::page>
    @php
        $org = auth()->user()->organization;
        $account = $this->stripeAccount();
    @endphp

    <x-filament::section heading="Stripe" icon="heroicon-o-currency-dollar">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                    @if ($org && $org->stripe_onboarded)
                        <p class="mt-1 text-sm font-semibold text-teal-600 dark:text-teal-400">
                            <img src="{{ asset('icons/250px-Eo_circle_green_checkmark.svg.png') }}" class="inline size-6" alt="Connected" />
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
            @endif

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
