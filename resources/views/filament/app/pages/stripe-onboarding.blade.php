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

            <a
                href="{{ route('stripe.connect.redirect') }}"
                class="mt-10 inline-flex items-center gap-2 rounded-lg bg-teal-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <x-heroicon-o-link class="size-5" />
                Sambung Akaun Stripe
            </a>
        @endif

        <p class="mt-8 text-sm text-gray-400">
            Perlukan bantuan? Sila hubungi admin organisasi anda.
        </p>
    </div>
</x-filament::page>
