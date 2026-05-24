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

    <x-filament::section heading="Notifikasi" icon="heroicon-o-bell-alert">
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ([
                    ['label' => 'Notifikasi derma baharu', 'desc' => 'Terima e-mel untuk setiap derma berjaya', 'prop' => 'notifyNewDonation', 'active' => $notifyNewDonation],
                    ['label' => 'Ringkasan derma harian', 'desc' => 'Terima ringkasan semua derma yang diterima setiap hari', 'prop' => 'dailyDonationSummary', 'active' => $dailyDonationSummary],
                    ['label' => 'Notifikasi bayaran bulanan gagal', 'desc' => 'Amaran apabila bayaran berulang penderma gagal diproses', 'prop' => 'failedPaymentNotification', 'active' => $failedPaymentNotification],
                ] as $row)
                    <div class="flex items-center gap-4 px-5 py-4">
                        <button
                            type="button"
                            role="switch"
                            aria-checked="{{ $row['active'] ? 'true' : 'false' }}"
                            wire:click="$toggle('{{ $row['prop'] }}')"
                            class="relative shrink-0 inline-flex h-6 w-10 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 {{ $row['active'] ? 'bg-teal-600' : 'bg-gray-200 dark:bg-gray-600' }}"
                        >
                            <span class="pointer-events-none inline-block size-4 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $row['active'] ? 'translate-x-[18px]' : 'translate-x-0.5' }}"></span>
                        </button>
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $row['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-5 py-2.5 dark:border-gray-700 dark:bg-gray-800/50">
                <p class="text-xs text-gray-400">Perubahan disimpan secara automatik.</p>
            </div>
        </div>
    </x-filament::section>
</x-filament::page>
