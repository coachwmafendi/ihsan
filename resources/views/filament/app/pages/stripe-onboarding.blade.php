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

        <div class="mt-8 grid gap-4 text-left">
            <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">1</div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Daftar akaun Stripe</p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Masukkan maklumat organisasi dan bank anda.</p>
                </div>
            </div>
            <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">2</div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Sahkan maklumat</p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Stripe akan mengesahkan maklumat organisasi anda.</p>
                </div>
            </div>
            <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">3</div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Mula terima derma</p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Selepas selesai, anda boleh guna panel sepenuhnya.</p>
                </div>
            </div>
        </div>

        <div class="mt-10">
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
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    Akaun Stripe belum disediakan. Sila hubungi admin.
                </div>
            @endif
        </div>

        <p class="mt-6 text-sm text-gray-400">
            Arahkan admin organisasi anda jika anda tidak mempunyai maklumat yang diperlukan.
        </p>
    </div>
</x-filament::page>
