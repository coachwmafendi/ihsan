<div class="space-y-6">
    <x-ui.page-header title="Settings">
        <x-slot:subtitle>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-slate-500">
                    <li>Settings</li>
                    <li>
                        <svg class="mx-1 h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="font-medium text-slate-900">Payment</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>



    @php
        $org = Auth::user()?->organization;
        $account = $this->stripeAccount();
    @endphp

    {{-- Stripe Connect --}}
    <x-ui.card title="Stripe Connect" description="Manage your payment processing settings and Stripe connection.">
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                @if ($org && $org->stripe_onboarded)
                    <x-ui.status-badge status="success" size="sm">Connected</x-ui.status-badge>
                @else
                    <x-ui.status-badge status="warning" size="sm">Incomplete</x-ui.status-badge>
                @endif
            </div>

            @if ($org && $org->stripe_account_id)
                <div class="flex items-center justify-between text-sm">
                    <div class="font-medium text-slate-700">{{ $org->name }}</div>
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-600">
                        {{ $org->stripe_account_id }}
                    </span>
                </div>
            @endif

            @if ($account)
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Charges</p>
                        <p class="mt-0.5 text-sm font-semibold {{ $account->charges_enabled ? 'text-teal-600' : 'text-red-600' }}">
                            {{ $account->charges_enabled ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Payouts</p>
                        <p class="mt-0.5 text-sm font-semibold {{ $account->payouts_enabled ? 'text-teal-600' : 'text-red-600' }}">
                            {{ $account->payouts_enabled ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Onboarding</p>
                        <p class="mt-0.5 text-sm font-semibold {{ $account->details_submitted ? 'text-teal-600' : 'text-amber-600' }}">
                            {{ $account->details_submitted ? 'Completed' : 'Incomplete' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Default Currency</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-700 uppercase">
                            {{ $account->default_currency ?? '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Reconnect</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Disconnect the current Stripe Connect account and reconnect with a different one.
                        </p>
                    </div>
                    <x-ui.button variant="danger" size="sm" wireClick="$set('showReconnectConfirm', true)">
                        Reconnect
                    </x-ui.button>
                </div>
            @else
                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Connect your account</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Link your Stripe account to start accepting donations.
                        </p>
                    </div>
                    <x-ui.button variant="primary" size="sm" href="/app/stripe-onboarding">
                        Connect with Stripe
                    </x-ui.button>
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- Accepted Currencies --}}
    <x-ui.card title="Accepted Currencies" description="Select the currencies donors can use to make donations. Malaysian Ringgit (MYR) is always enabled.">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-4">
                <input
                    type="checkbox"
                    wire:model.live="currencies.myr"
                    checked
                    disabled
                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                />
                <div>
                    <p class="text-sm font-semibold text-slate-900">MYR</p>
                    <p class="text-xs text-slate-500">Ringgit Malaysia</p>
                </div>
            </label>

            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-4">
                <input
                    type="checkbox"
                    wire:model.live="currencies.usd"
                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                />
                <div>
                    <p class="text-sm font-semibold text-slate-900">USD</p>
                    <p class="text-xs text-slate-500">US Dollar</p>
                </div>
            </label>

            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-4">
                <input
                    type="checkbox"
                    wire:model.live="currencies.sgd"
                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                />
                <div>
                    <p class="text-sm font-semibold text-slate-900">SGD</p>
                    <p class="text-xs text-slate-500">Singapore Dollar</p>
                </div>
            </label>
        </div>
    </x-ui.card>

    {{-- Billing & Fees --}}
    {{-- <x-ui.card title="Billing & Fees" description="Choose how processing fees ({{ $this->getProcessingFeePercent() }}%) are collected.">
        <div class="max-w-xs space-y-3">
            <div class="relative">
                <select
                    wire:model="feeCollectionMethod"
                    class="block w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                >
                    <option value="invoice">Monthly Invoice</option>
                    <option value="deduct">Upfront Deduction</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            <x-ui.button variant="primary" size="sm" wireClick="saveFeeCollection">
                Save
            </x-ui.button>
        </div>
    </x-ui.card> --}}

    {{-- Reconnect Confirmation Modal --}}
    @if ($showReconnectConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('showReconnectConfirm', false)">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-slate-900">Reconnect Stripe Connect?</h3>
                <p class="mt-2 text-sm text-slate-500">
                    This will disconnect your current Stripe Connect account. You will need to reconnect a Stripe account to continue using the panel.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-ui.button variant="ghost" size="sm" wireClick="$set('showReconnectConfirm', false)">
                        Cancel
                    </x-ui.button>
                    <x-ui.button variant="danger" size="sm" wireClick="reconnect">
                        Yes, reconnect
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
