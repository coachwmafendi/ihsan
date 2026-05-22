<x-filament::page>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="mx-auto mb-8 flex size-20 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
            <x-heroicon-o-link class="size-10 text-amber-600 dark:text-amber-400" />
        </div>

        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Selesaikan Onboarding Stripe
        </h1>

        <p class="mt-3 max-w-md text-base text-gray-500 dark:text-gray-400">
            Anda perlu menyambung akaun Stripe sebelum boleh menggunakan panel ini.
            Stripe digunakan untuk memproses derma dalam talian dengan selamat.
        </p>

        @php
            $url = $this->getOnboardingUrl();
        @endphp

        @if ($url)
            <div class="mt-10">
                <a
                    href="{{ $url }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                >
                    <x-heroicon-o-arrow-right-circle class="size-5" />
                    Sambung Stripe
                </a>
            </div>
        @else
            <div class="mt-10 flex flex-col items-center gap-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Pilih cara untuk menyambung Stripe:
                </p>

                <div class="grid w-full max-w-lg gap-4">
                    <button
                        type="button"
                        wire:click="createStripeAccount"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <x-heroicon-o-sparkles class="size-5" />
                        Buat akaun Stripe baru
                    </button>

                    <div class="relative flex items-center gap-3 py-2">
                        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                        <span class="text-xs font-medium text-gray-400">ATAU</span>
                        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                    </div>

                    <form wire:submit="connectManualAccount" class="space-y-3">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                wire:model="manual_account_id"
                                placeholder="acct_xxxxxxxxxxxxxx"
                            />
                        </x-filament::input.wrapper>
                        <x-filament::button type="submit" color="gray">
                            Sambung akaun sedia ada
                        </x-filament::button>
                    </form>
                </div>
            </div>
        @endif

        <p class="mt-8 text-sm text-gray-400">
            Perlukan bantuan? Sila hubungi admin organisasi anda.
        </p>
    </div>
</x-filament::page>
