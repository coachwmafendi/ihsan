<x-filament::page>
    @php
        $org = auth()->user()->organization;
        $needsStripe = $org && $org->stripe_account_id && ! $org->stripe_onboarded;
    @endphp

    @if ($needsStripe)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">Stripe Onboarding</h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">Sambung akaun Stripe untuk mula menerima derma.</p>
                </div>
                <a
                    href="{{ app(App\Actions\Stripe\CreateConnectAccount::class)->generateOnboardingLink($org) }}"
                    class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                >
                    Sambung Stripe
                </a>
            </div>
        </div>
    @endif

    @livewire(\Filament\Widgets\AccountWidget::class)
</x-filament::page>
