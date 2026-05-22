<x-filament::page>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="mx-auto mb-8 flex items-center justify-center gap-4">
            <div class="flex h-10 w-24 items-center justify-center rounded-lg bg-white px-3 dark:bg-gray-800">
                <img src="https://www.kleer.se/wp-content/uploads/2025/10/Stripe-Emblem-scaled.png" alt="Stripe" class="h-full w-full object-contain">
            </div>
            <div class="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                <x-heroicon-o-link class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
        </div>

        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Selesaikan Onboarding Stripe
        </h1>

        @php
            $org = auth()->user()->organization;
            $hasAccountPending = $org && $org->stripe_account_id && ! $org->stripe_onboarded;
            $hasClientId = config('services.stripe.connect_client_id');
        @endphp

        @if ($hasAccountPending)
            <p class="mt-3 max-w-md text-base text-amber-600 dark:text-amber-400">
                Akaun Stripe telah disimpan tetapi belum lengkap.
            </p>

            <p class="mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Sila selesaikan proses pengesahan (KYC) di dashboard Stripe, kemudian semak semula.
            </p>

            <div class="mt-8">
                @php
                    $url = $this->getOnboardingUrl();
                @endphp

                @if ($url)
                    <a
                        href="{{ $url }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <x-heroicon-o-arrow-right-circle class="size-5" />
                        Sambung Stripe
                    </a>
                @else
                    <p class="text-sm text-gray-400">
                        Muat semula halaman ini selepas melengkapkan KYC di Stripe.
                    </p>
                @endif
            </div>
        @else
            <p class="mt-3 max-w-md text-base text-gray-500 dark:text-gray-400">
                Anda perlu menyambung akaun Stripe sebelum boleh menggunakan panel ini.
                Stripe digunakan untuk memproses derma dalam talian dengan selamat.
            </p>

            <button
                type="button"
                wire:click="createExpressAccount"
                class="mt-10 inline-flex items-center gap-2 rounded-lg bg-teal-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <x-heroicon-o-sparkles class="size-5" />
                Buat akaun Stripe baru
            </button>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @if ($hasClientId)
                    <a
                        href="{{ route('stripe.connect.redirect') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-teal-600 px-5 py-2.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-teal-500 dark:text-teal-400 dark:hover:bg-teal-950"
                    >
                        <x-heroicon-o-link class="size-4" />
                        Connect akaun sedia ada
                    </a>
                @endif

                <a
                    href="https://dashboard.stripe.com/register"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                    Daftar di Stripe
                </a>
            </div>

            <div class="mt-12 w-full max-w-sm border-t border-gray-200 pt-6 dark:border-gray-700">
                <p class="mb-2 text-left text-xs font-medium text-gray-400">
                    Atau masukkan ID akaun Stripe secara manual:
                </p>
                <form>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model="manual_account_id"
                            placeholder="acct_xxx"
                            class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder-gray-500"
                        >
                        <button
                            type="button"
                            wire:click="connectManualAccount"
                            class="shrink-0 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Sambung
                        </button>
                    </div>
                    @error('manual_account_id')
                        <p class="mt-1 text-left text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            <p class="mt-6 text-sm text-gray-400">
                Untuk info lanjut berkenaan Stripe account ID, lawati
                <a href="https://docs.stripe.com/get-started/account" target="_blank" rel="noopener noreferrer" class="text-teal-600 underline hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300">docs.stripe.com</a>
            </p>
        @endif

        <p class="mt-8 text-sm text-gray-400">
            Perlukan bantuan? Sila hubungi admin organisasi anda.
        </p>
    </div>
</x-filament::page>
