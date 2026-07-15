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
                <x-app-logo-icon class="h-7 w-7 shrink-0" />
                <span class="text-lg font-semibold tracking-tight text-teal-700 dark:text-teal-400">ihsan</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4">
            <x-sidebar-group label="Fundraise">
                <x-sidebar-item href="/app/dashboard" icon="home" label="Dashboard" :active="$isActive('/app/dashboard')" />
                <x-sidebar-item href="/app/campaigns" icon="megaphone" label="Campaigns" :active="$isActive('/app/campaigns')" />
                <x-sidebar-item href="/app/elements" icon="cursor-arrow-rays" label="Elements" :active="$isActive('/app/elements')" />

            </x-sidebar-group>

            <x-sidebar-group label="Finance">
                <x-sidebar-item href="/app/donations" icon="banknotes" label="Donations" :active="$isActive('/app/donations')" />
                <x-sidebar-item href="/app/recurring-plans" icon="arrow-path" label="Recurring Plans" :active="$isActive('/app/recurring-plans') || $isActive('/app/subscriptions')" />
                <x-sidebar-item href="/app/supporters" icon="users" label="Supporters" :active="$isActive('/app/supporters')" />
                <x-sidebar-item href="/app/virtual-terminal" icon="device-phone-mobile" label="Virtual Terminal" target="_blank" />
            </x-sidebar-group>

            <x-sidebar-group label="Organization">
                <x-sidebar-item href="/app/audit-log" icon="clipboard-document-list" label="Audit Log" :active="$isActive('/app/audit-log')" />

                <x-sidebar-dropdown icon="cog-6-tooth" label="Settings" :active="$isActive('/app/settings')">
                    <x-sidebar-item href="/app/settings/organization" icon="building-office" label="Organization" :active="$isActive('/app/settings/organization')" />
                    <x-sidebar-item href="/app/settings/payment" icon="credit-card" label="Payment Processors" :active="$isActive('/app/settings/payment')" />
                    <x-sidebar-item href="/app/settings/account" icon="user-circle" label="Account" :active="$isActive('/app/settings/account')" />
                    <x-sidebar-item href="/app/settings/allow-domains" icon="globe-alt" label="Allowed Domains" :active="$isActive('/app/settings/allow-domains')" />
                    <x-sidebar-item href="/app/settings/donor-portal" icon="icon.donor-portal" label="Donor Portal" :active="$isActive('/app/settings/donor-portal')" />
                    <x-sidebar-item href="/app/settings/notifications" icon="bell-alert" label="Notifications" :active="$isActive('/app/settings/notifications')" />
                    <x-sidebar-item href="/app/settings/installation" icon="code-bracket" label="Installation" :active="$isActive('/app/settings/installation')" />
                    <x-sidebar-item href="/app/settings/tracking" icon="presentation-chart-line" label="Tracking & Analytics" :active="$isActive('/app/settings/tracking')" />
                </x-sidebar-dropdown>
            </x-sidebar-group>

            <x-sidebar-dropdown icon="question-mark-circle" label="Help">
                <x-sidebar-item href="mailto:support@getihsan.my" icon="envelope" label="Email Support" target="_self" />
                <x-sidebar-item href="https://ihsan.test:8443/docs" icon="book-open" label="Documentation" target="_blank" />
            </x-sidebar-dropdown>
        </nav>
    </div>

    {{-- Desktop sidebar --}}
    <div
        id="app-sidebar-desktop"
        class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 bg-white border-r border-slate-200"
        :class="$store.sidebar.collapsed ? 'lg:w-16' : 'lg:w-64'"
    >
        <div
            class="h-16 flex items-center border-b border-slate-200 shrink-0"
            :class="$store.sidebar.collapsed ? 'px-3 justify-center' : 'px-6 justify-between'"
        >
            <div
                class="flex items-center overflow-hidden"
                :class="$store.sidebar.collapsed ? '' : 'gap-2.5'"
                x-show="! $store.sidebar.collapsed"
                x-cloak
            >
                <x-app-logo-icon class="h-7 w-7 shrink-0" />
                <span class="text-lg font-semibold tracking-tight text-teal-700 dark:text-teal-400 whitespace-nowrap">ihsan</span>
            </div>
            <button
                type="button"
                @click="$store.sidebar.toggle()"
                class="hidden lg:flex p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus-visible:ring-0"
                :title="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <x-icon.sidebar-panel class="size-5" />
            </button>
        </div>

        <nav class="flex-1 overflow-x-hidden overflow-y-auto py-4">
            <x-sidebar-group label="Fundraise">
                <x-sidebar-item href="/app/dashboard" icon="home" label="Dashboard" :active="$isActive('/app/dashboard')" />
                <x-sidebar-item href="/app/campaigns" icon="megaphone" label="Campaigns" :active="$isActive('/app/campaigns')" />
                <x-sidebar-item href="/app/elements" icon="cursor-arrow-rays" label="Elements" :active="$isActive('/app/elements')" />

            </x-sidebar-group>

            <x-sidebar-group label="Finance">
                <x-sidebar-item href="/app/donations" icon="banknotes" label="Donations" :active="$isActive('/app/donations')" />
                <x-sidebar-item href="/app/recurring-plans" icon="arrow-path" label="Recurring Plans" :active="$isActive('/app/recurring-plans') || $isActive('/app/subscriptions')" />
                <x-sidebar-item href="/app/supporters" icon="users" label="Supporters" :active="$isActive('/app/supporters')" />
                <x-sidebar-item href="/app/virtual-terminal" icon="device-phone-mobile" label="Virtual Terminal" target="_blank" />
            </x-sidebar-group>

            <x-sidebar-group label="Organization">
                <x-sidebar-item href="/app/audit-log" icon="clipboard-document-list" label="Audit Log" :active="$isActive('/app/audit-log')" />

                <x-sidebar-dropdown icon="cog-6-tooth" label="Settings" :active="$isActive('/app/settings')">
                    <x-sidebar-item href="/app/settings/organization" icon="building-office" label="Organization" :active="$isActive('/app/settings/organization')" />
                    <x-sidebar-item href="/app/settings/payment" icon="credit-card" label="Payment Processors" :active="$isActive('/app/settings/payment')" />
                    <x-sidebar-item href="/app/settings/account" icon="user-circle" label="Account" :active="$isActive('/app/settings/account')" />
                    <x-sidebar-item href="/app/settings/allow-domains" icon="globe-alt" label="Allowed Domains" :active="$isActive('/app/settings/allow-domains')" />
                    <x-sidebar-item href="/app/settings/donor-portal" icon="icon.donor-portal" label="Donor Portal" :active="$isActive('/app/settings/donor-portal')" />
                    <x-sidebar-item href="/app/settings/notifications" icon="bell-alert" label="Notifications" :active="$isActive('/app/settings/notifications')" />
                    <x-sidebar-item href="/app/settings/installation" icon="code-bracket" label="Installation" :active="$isActive('/app/settings/installation')" />
                    <x-sidebar-item href="/app/settings/tracking" icon="presentation-chart-line" label="Tracking & Analytics" :active="$isActive('/app/settings/tracking')" />
                </x-sidebar-dropdown>
            </x-sidebar-group>

            <x-sidebar-dropdown icon="question-mark-circle" label="Help">
                <x-sidebar-item href="mailto:support@getihsan.my" icon="envelope" label="Email Support" target="_self" />
                <x-sidebar-item href="https://ihsan.test:8443/docs" icon="book-open" label="Documentation" target="_blank" />
            </x-sidebar-dropdown>
        </nav>
    </div>
</div>
