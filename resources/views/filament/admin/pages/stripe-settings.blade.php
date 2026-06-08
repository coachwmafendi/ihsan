@php
    $platform = $this->getPlatformAccount();
    $summary = $this->getConnectedAccountsSummary();
    $config = $this->getConfigStatus();
    $apiMode = $this->getApiMode();
@endphp

<x-filament-panels::page>
    <div class="ihsan-admin-page space-y-4">

        {{-- Environment Mode Warning --}}
        @if ($apiMode['label'] === 'Live')
            <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 dark:border-red-800 dark:bg-red-950/30">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="size-6 text-red-600 dark:text-red-400" />
                    <div>
                        <p class="text-sm font-bold text-red-700 dark:text-red-300">
                            Live Mode Active
                        </p>
                        <p class="text-xs text-red-600 dark:text-red-400">
                            Real transactions will be processed through this Stripe account.
                        </p>
                    </div>
                </div>
            </div>
        @elseif ($apiMode['label'] === 'Test')
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-800 dark:bg-amber-950/30">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-beaker class="size-6 text-amber-600 dark:text-amber-400" />
                    <div>
                        <p class="text-sm font-bold text-amber-700 dark:text-amber-300">
                            Test Mode Active
                        </p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">
                            Transactions are simulated. No real payments will be processed.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Existing Processing Fee Form --}}
        <div class="rounded-lg border border-stone-200 bg-ihsan-cream p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex items-center gap-3">
                    <x-filament::button type="submit">
                        {{ __('Save') }}
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Connection Actions --}}
        <x-admin.settings-panel title="Connection Test">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-stone-600 dark:text-stone-400">
                        Verify that your Stripe credentials are working correctly.
                    </p>
                </div>
                <x-filament::button
                    wire:click="verifyConnection"
                    icon="heroicon-o-bolt"
                    color="primary"
                >
                    Verify Connection
                </x-filament::button>
            </div>
        </x-admin.settings-panel>

        {{-- Platform Account --}}
        <x-admin.settings-panel title="Platform Account">
            @if ($platform)
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Account ID</span><p class="font-mono text-sm font-medium">{{ $platform['id'] }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Business Name</span><p class="text-sm font-medium">{{ $platform['business_name'] ?? '—' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Email</span><p class="text-sm font-medium">{{ $platform['email'] ?? '—' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Country</span><p class="text-sm font-medium">{{ $platform['country'] ?? '—' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Default Currency</span><p class="text-sm font-medium uppercase">{{ $platform['default_currency'] ?? '—' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Charges</span><p class="text-sm font-medium {{ $platform['charges_enabled'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $platform['charges_enabled'] ? 'Enabled' : 'Disabled' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Payouts</span><p class="text-sm font-medium {{ $platform['payouts_enabled'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $platform['payouts_enabled'] ? 'Enabled' : 'Disabled' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">API Mode</span><p class="text-sm font-medium {{ $apiMode['color'] === 'danger' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $apiMode['label'] }}</p></div>
                </div>
            @else
                <p class="text-sm text-stone-500">Unable to retrieve platform account details. Check your Stripe secret key configuration.</p>
            @endif
        </x-admin.settings-panel>

        {{-- Connected Accounts Summary --}}
        <x-admin.settings-panel title="Connected Accounts">
            <div class="grid grid-cols-2 gap-3">
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Total Organizations</span><p class="text-sm font-medium">{{ $summary['total_organizations'] }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Stripe Connected</span><p class="text-sm font-medium">{{ $summary['connected'] }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Onboarded</span><p class="text-sm font-medium text-teal-600 dark:text-teal-400">{{ $summary['onboarded'] }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Pending Onboarding</span><p class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ $summary['pending_onboarding'] }}</p></div>
            </div>
        </x-admin.settings-panel>

        {{-- Config Status + Endpoints --}}
        <x-admin.settings-panel title="Configuration & Endpoints">
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Secret Key</span><p class="text-sm font-medium {{ $config['secret_key'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $config['secret_key'] ? 'Configured' : 'Missing' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Webhook Secret</span><p class="text-sm font-medium {{ $config['webhook_secret'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $config['webhook_secret'] ? 'Configured' : 'Missing' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Connect Client ID</span><p class="text-sm font-medium {{ $config['connect_client_id'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $config['connect_client_id'] ? 'Configured' : 'Missing' }}</p></div>
                    <div><span class="text-xs text-stone-500 dark:text-stone-400">Processing Fee</span><p class="text-sm font-medium">{{ $config['processing_fee_percent'] }}%</p></div>
                </div>

                <hr class="border-stone-200 dark:border-stone-700">

                <div>
                    <p class="text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1.5">Webhook Endpoint URL</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 rounded-md bg-stone-100 px-3 py-2 text-xs font-mono text-stone-700 break-all dark:bg-stone-800 dark:text-stone-300">{{ $this->getWebhookUrl() }}</code>
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText('{{ $this->getWebhookUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="shrink-0 rounded-md bg-stone-200 px-3 py-2 text-xs font-medium text-stone-700 transition hover:bg-stone-300 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600"
                            x-text="copied ? 'Copied!' : 'Copy'"
                        >Copy</button>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1.5">Connect Redirect URI</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 rounded-md bg-stone-100 px-3 py-2 text-xs font-mono text-stone-700 break-all dark:bg-stone-800 dark:text-stone-300">{{ $config['redirect_uri'] }}</code>
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText('{{ $config['redirect_uri'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="shrink-0 rounded-md bg-stone-100 px-3 py-2 text-xs font-medium text-stone-700 transition hover:bg-stone-300 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600"
                            x-text="copied ? 'Copied!' : 'Copy'"
                        >Copy</button>
                    </div>
                </div>
            </div>
        </x-admin.settings-panel>

    </div>
</x-filament-panels::page>
