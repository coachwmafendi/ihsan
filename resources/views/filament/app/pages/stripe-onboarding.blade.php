<x-filament::page>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        @if (session('error'))
            <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif
        <div class="mx-auto mb-8 flex items-center justify-center gap-4">
            <div class="flex h-10 w-24 items-center justify-center rounded-lg bg-white px-3 dark:bg-gray-800">
                <img src="https://www.kleer.se/wp-content/uploads/2025/10/Stripe-Emblem-scaled.png" alt="Stripe" class="h-full w-full object-contain">
            </div>
            <div class="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                <x-heroicon-o-link class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
        </div>

        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Complete Stripe Connect Onboarding
        </h1>

        @php
            $org = auth()->user()->organization;
            $hasAccountPending = $org && $org->stripe_account_id && ! $org->stripe_onboarded;
            $hasClientId = config('services.stripe.connect_client_id');
        @endphp

        @if ($hasAccountPending)
            <p class="mt-3 max-w-md text-base text-amber-600 dark:text-amber-400">
                Stripe Connect account has been created but is not yet complete.
            </p>

            <p class="mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Please complete the verification (KYC) process in the Stripe Connect dashboard, then check back.
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
                        Continue Stripe Connect
                    </a>
                @else
                    <p class="text-sm text-gray-400">
                        Reload this page after completing KYC in Stripe Connect.
                    </p>
                @endif
            </div>
        @else
            <p class="mt-3 max-w-md text-base text-gray-500 dark:text-gray-400">
                You need to connect a Stripe account before using this panel.
                Stripe Connect is used to process online donations securely.
            </p>

            <a
                href="{{ route('stripe.connect.redirect') }}"
                target="_top"
                class="mt-10 inline-flex items-center gap-2 rounded-lg bg-teal-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <x-heroicon-o-link class="size-5" />
                Connect Stripe Account
            </a>
        @endif

        <p class="mt-8 text-sm text-gray-400">
            Need help? Please contact your organization admin.
        </p>
    </div>
</x-filament::page>
