<x-filament-panels::page>
    <div class="ihsan-admin-page space-y-4">
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

        {{-- API Mode --}}
        <x-admin.settings-panel title="Stripe API Mode">
            Current mode: <span class="font-medium">{{ $this->getApiMode() }}</span>
        </x-admin.settings-panel>

        @php
            $platform = $this->getPlatformAccount();
            $summary = $this->getConnectedAccountsSummary();
            $config = $this->getConfigStatus();
            $revenue = $this->getPlatformRevenue();
        @endphp

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

        {{-- Config Status --}}
        <x-admin.settings-panel title="Configuration">
            <div class="grid grid-cols-2 gap-3">
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Secret Key</span><p class="text-sm font-medium {{ $config['secret_key'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $config['secret_key'] ? 'Configured' : 'Missing' }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Webhook Secret</span><p class="text-sm font-medium {{ $config['webhook_secret'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $config['webhook_secret'] ? 'Configured' : 'Missing' }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Connect Client ID</span><p class="text-sm font-medium {{ $config['connect_client_id'] ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">{{ $config['connect_client_id'] ? 'Configured' : 'Missing' }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Redirect URI</span><p class="font-mono text-xs break-all">{{ $config['redirect_uri'] }}</p></div>
            </div>
        </x-admin.settings-panel>

        {{-- Platform Revenue --}}
        <x-admin.settings-panel title="Platform Revenue">
            <div class="grid grid-cols-3 gap-3">
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Total Fees</span><p class="text-sm font-medium">MYR {{ number_format($revenue['total'], 2) }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Paid</span><p class="text-sm font-medium text-teal-600 dark:text-teal-400">MYR {{ number_format($revenue['paid'], 2) }}</p></div>
                <div><span class="text-xs text-stone-500 dark:text-stone-400">Pending</span><p class="text-sm font-medium text-amber-600 dark:text-amber-400">MYR {{ number_format($revenue['pending'], 2) }}</p></div>
            </div>
        </x-admin.settings-panel>
    </div>
</x-filament-panels::page>
