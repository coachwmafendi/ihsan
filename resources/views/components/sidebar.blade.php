{{-- resources/views/components/sidebar.blade.php --}}
@php
$isActive = fn (string $path): bool => request()->is(trim($path, '/')) || request()->is(trim($path, '/') . '/*');
@endphp

<div x-data="{ mobileOpen: false }" @toggle-sidebar.window="mobileOpen = !mobileOpen">
    {{-- Mobile overlay --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        @click="mobileOpen = false"
    ></div>

    {{-- Mobile drawer --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 lg:hidden flex flex-col"
    >
        <div class="h-16 flex items-center px-6 border-b border-slate-200 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded bg-teal-600 flex items-center justify-center text-white font-bold text-sm">i</div>
                <span class="font-bold text-lg text-slate-900 tracking-tight">ihsan</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4">
            <x-sidebar-group label="Fundraise">
                <x-sidebar-item href="/app/dashboard" icon="home" label="Dashboard" :active="$isActive('/app/dashboard')" />
                <x-sidebar-item href="/app/campaigns" icon="megaphone" label="Campaigns" :active="$isActive('/app/campaigns')" />
                <x-sidebar-item href="/app/elements" icon="cursor-arrow-rays" label="Elements" :active="$isActive('/app/elements')" />
                <x-sidebar-item href="/app/insights" icon="chart-bar" label="Insights" :active="$isActive('/app/insights')" />
            </x-sidebar-group>

            <x-sidebar-group label="Finance">
                <x-sidebar-item href="/app/donations" icon="banknotes" label="Donations" :active="$isActive('/app/donations')" />
                <x-sidebar-item href="/app/recurring-plans" icon="arrow-path" label="Recurring Plans" :active="$isActive('/app/recurring-plans')" />
                <x-sidebar-item href="/app/payouts" icon="wallet" label="Payouts" :active="$isActive('/app/payouts')" />
                <x-sidebar-item href="/app/billing" icon="credit-card" label="Billing" :active="$isActive('/app/billing')" />
            </x-sidebar-group>

            <x-sidebar-group label="Supporters">
                <x-sidebar-item href="/app/donors" icon="users" label="Donors" :active="$isActive('/app/donors')" />
                <x-sidebar-item href="/app/subscriptions" icon="arrow-path-rounded-square" label="Subscriptions" :active="$isActive('/app/subscriptions')" />
            </x-sidebar-group>

            <x-sidebar-group label="Organization">
                <x-sidebar-item href="/app/members" icon="user-group" label="Members" :active="$isActive('/app/members')" />
                <x-sidebar-item href="/app/teams" icon="rectangle-group" label="Teams" :active="$isActive('/app/teams')" />
                
                <x-sidebar-dropdown icon="cog-6-tooth" label="Settings" :active="$isActive('/app/settings')">
                    <x-sidebar-item href="/app/settings/profile" icon="building-office" label="Profile" :active="$isActive('/app/settings/profile')" />
                    <x-sidebar-item href="/app/settings/payment" icon="credit-card" label="Stripe Connect" :active="$isActive('/app/settings/payment')" />
                    <x-sidebar-item href="/app/settings/notifications" icon="bell-alert" label="Notifications" :active="$isActive('/app/settings/notifications')" />
                </x-sidebar-dropdown>
                
                <x-sidebar-item href="/app/virtual-terminal" icon="device-phone-mobile" label="Virtual Terminal" :active="$isActive('/app/virtual-terminal')" />
            </x-sidebar-group>

            <x-sidebar-group label="API & Developer">
                <x-sidebar-item href="/app/developer/api-keys" icon="key" label="API Keys" :active="$isActive('/app/developer/api-keys')" />
                <x-sidebar-item href="/app/developer/webhooks" icon="globe-alt" label="Webhooks" :active="$isActive('/app/developer/webhooks')" />
                <x-sidebar-item href="/app/developer/embed-forms" icon="code-bracket" label="Embed Forms" :active="$isActive('/app/developer/embed-forms')" />
            </x-sidebar-group>
        </nav>
    </div>

    {{-- Desktop sidebar --}}
    <div class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-slate-200">
        <div class="h-16 flex items-center px-6 border-b border-slate-200 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded bg-teal-600 flex items-center justify-center text-white font-bold text-sm">i</div>
                <span class="font-bold text-lg text-slate-900 tracking-tight">ihsan</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4">
            <x-sidebar-group label="Fundraise">
                <x-sidebar-item href="/app/dashboard" icon="home" label="Dashboard" :active="$isActive('/app/dashboard')" />
                <x-sidebar-item href="/app/campaigns" icon="megaphone" label="Campaigns" :active="$isActive('/app/campaigns')" />
                <x-sidebar-item href="/app/elements" icon="cursor-arrow-rays" label="Elements" :active="$isActive('/app/elements')" />
                <x-sidebar-item href="/app/insights" icon="chart-bar" label="Insights" :active="$isActive('/app/insights')" />
            </x-sidebar-group>

            <x-sidebar-group label="Finance">
                <x-sidebar-item href="/app/donations" icon="banknotes" label="Donations" :active="$isActive('/app/donations')" />
                <x-sidebar-item href="/app/recurring-plans" icon="arrow-path" label="Recurring Plans" :active="$isActive('/app/recurring-plans')" />
                <x-sidebar-item href="/app/payouts" icon="wallet" label="Payouts" :active="$isActive('/app/payouts')" />
                <x-sidebar-item href="/app/billing" icon="credit-card" label="Billing" :active="$isActive('/app/billing')" />
            </x-sidebar-group>

            <x-sidebar-group label="Supporters">
                <x-sidebar-item href="/app/donors" icon="users" label="Donors" :active="$isActive('/app/donors')" />
                <x-sidebar-item href="/app/subscriptions" icon="arrow-path-rounded-square" label="Subscriptions" :active="$isActive('/app/subscriptions')" />
            </x-sidebar-group>

            <x-sidebar-group label="Organization">
                <x-sidebar-item href="/app/members" icon="user-group" label="Members" :active="$isActive('/app/members')" />
                <x-sidebar-item href="/app/teams" icon="rectangle-group" label="Teams" :active="$isActive('/app/teams')" />
                
                <x-sidebar-dropdown icon="cog-6-tooth" label="Settings" :active="$isActive('/app/settings')">
                    <x-sidebar-item href="/app/settings/profile" icon="building-office" label="Profile" :active="$isActive('/app/settings/profile')" />
                    <x-sidebar-item href="/app/settings/payment" icon="credit-card" label="Stripe Connect" :active="$isActive('/app/settings/payment')" />
                    <x-sidebar-item href="/app/settings/notifications" icon="bell-alert" label="Notifications" :active="$isActive('/app/settings/notifications')" />
                </x-sidebar-dropdown>
                
                <x-sidebar-item href="/app/virtual-terminal" icon="device-phone-mobile" label="Virtual Terminal" :active="$isActive('/app/virtual-terminal')" />
            </x-sidebar-group>

            <x-sidebar-group label="API & Developer">
                <x-sidebar-item href="/app/developer/api-keys" icon="key" label="API Keys" :active="$isActive('/app/developer/api-keys')" />
                <x-sidebar-item href="/app/developer/webhooks" icon="globe-alt" label="Webhooks" :active="$isActive('/app/developer/webhooks')" />
                <x-sidebar-item href="/app/developer/embed-forms" icon="code-bracket" label="Embed Forms" :active="$isActive('/app/developer/embed-forms')" />
            </x-sidebar-group>
        </nav>
    </div>
</div>
