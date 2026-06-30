<div x-data="{ tab: @entangle('activeTab') }" class="space-y-6">
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
                    <li class="font-medium text-slate-900">Payment Processors</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>

    @php
        $org = Auth::user()?->organization;
        $account = $this->stripeAccount();
    @endphp

    {{-- Gateway tabs --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Payment gateways">
            <button type="button"
                @click="tab = 'stripe'"
                :class="tab === 'stripe' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Stripe
            </button>
            <button type="button"
                @click="tab = 'chip'"
                :class="tab === 'chip' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                CHIP
            </button>
        </nav>
    </div>

    {{-- Stripe tab --}}
    <div x-show="tab === 'stripe'" x-cloak class="space-y-6">
        <x-ui.card title="Stripe" description="Manage your Stripe account for card, wallet and international donations.">
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
                                Disconnect the current Stripe account and reconnect with a different one.
                            </p>
                        </div>
                        <x-ui.button variant="danger" wireClick="$set('showReconnectConfirm', true)">
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
    </div>

    {{-- CHIP tab --}}
    <div x-show="tab === 'chip'" x-cloak class="space-y-6">
        <x-ui.card title="CHIP Configuration" description="Enter your CHIP Brand ID and API key to accept donations via cards and FPX.">
            <div class="space-y-4">
                @php
                    $chipOnboarded = $org && $org->chip_onboarded;
                @endphp

                <div class="flex items-center gap-2">
                    @if ($chipOnboarded)
                        <x-ui.status-badge status="success" size="sm">Configured</x-ui.status-badge>
                    @else
                        <x-ui.status-badge status="warning" size="sm">Incomplete</x-ui.status-badge>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="chipBrandId" class="block text-sm font-medium text-slate-700">Brand ID</label>
                        <input
                            type="text"
                            id="chipBrandId"
                            wire:model="chipBrandId"
                            placeholder="e.g. BRAND123"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                        @error('chipBrandId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="chipApiKey" class="block text-sm font-medium text-slate-700">API Key</label>
                        <div class="relative mt-1">
                            <input
                                id="chipApiKey"
                                wire:model="chipApiKey"
                                placeholder="sk_..."
                                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-24 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                :type="$wire.showChipApiKey ? 'text' : 'password'"
                            />
                            <button
                                type="button"
                                wire:click="toggleChipApiKey"
                                class="absolute inset-y-1 right-1 rounded-md bg-slate-100 px-2.5 text-xs font-medium text-slate-600 hover:bg-slate-200"
                            >
                                <span x-text="$wire.showChipApiKey ? 'Hide' : 'Show'">Show</span>
                            </button>
                        </div>
                        @error('chipApiKey') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if ($chipOnboarded)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-700">CHIP webhook URL</p>
                        <p class="mt-1 break-all font-mono text-xs text-slate-600">{{ route('chip.webhook') }}</p>
                        <p class="mt-2 text-xs text-slate-500">Paste this URL in your CHIP dashboard under Developers → Webhooks, then save the Webhook ID and public key below.</p>
                    </div>

                    <div>
                        <label for="chipWebhookId" class="block text-sm font-medium text-slate-700">Webhook ID</label>
                        <input
                            type="text"
                            id="chipWebhookId"
                            wire:model="chipWebhookId"
                            placeholder="e.g. 00000000-0000-0000-0000-000000000000"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                        @error('chipWebhookId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="chipWebhookPublicKey" class="block text-sm font-medium text-slate-700">Webhook Public Key</label>
                        <textarea
                            id="chipWebhookPublicKey"
                            wire:model="chipWebhookPublicKey"
                            rows="4"
                            placeholder="-----BEGIN PUBLIC KEY----- ..."
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        ></textarea>
                        @error('chipWebhookPublicKey') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700">Accepted payment methods</label>
                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-4">
                            <input
                                type="checkbox"
                                value="card"
                                wire:model="chipPaymentMethods"
                                class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                            />
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Card</p>
                                <p class="text-xs text-slate-500">Credit / debit card</p>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-4">
                            <input
                                type="checkbox"
                                value="fpx"
                                wire:model="chipPaymentMethods"
                                class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                            />
                            <div>
                                <p class="text-sm font-semibold text-slate-900">FPX</p>
                                <p class="text-xs text-slate-500">Online banking (Malaysia)</p>
                            </div>
                        </label>
                    </div>
                    @error('chipPaymentMethods') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-4">
                    <x-ui.button variant="primary" wireClick="saveChipSettings">
                        Save Changes
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Reconnect Confirmation Modal --}}
    @if ($showReconnectConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('showReconnectConfirm', false)">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-slate-900">Reconnect Stripe?</h3>
                <p class="mt-2 text-sm text-slate-500">
                    This will disconnect your current Stripe account. You will need to reconnect a Stripe account to continue using the panel.
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
