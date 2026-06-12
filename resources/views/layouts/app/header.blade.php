<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('filament.app.pages.insights') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('filament.app.pages.insights')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />
            
            <!-- Notification Bell -->
            <flux:dropdown align="end">
                <flux:dropdown.toggle class="relative group">
                    <flux:dropdown.icon class="h-5 w-5 text-gray-500 group-hover:text-gray-700" icon="heroicon-o-bell" />
                    <span class="absolute -top-1 -right-1 flex h-2 w-2 items-center justify-center rounded-full bg-red-500 text-xs font-medium text-white"
                          x-show="{{ auth()->user()->unread_notifications_count > 0 }}"
                          x-text="auth()->user()->unread_notifications_count > 99 ? '99+' : auth()->user()->unread_notifications_count"></span>
                </flux:dropdown.toggle>
                
                <flux:dropdown.menu class="w-64">
                    <div class="px-2 py-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900">{{ __('Notifications') }}</h3>
                            <flux:button
                                wire:click="markAllAsRead"
                                class="text-xs text-blue-600 hover:text-blue-800"
                            >
                                {{ __('Mark all as read') }}
                            </flux:button>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="space-y-2">
                        @forelse ($notifications = auth()->user()->notifications()->take(5)->get() as $notification)
                            <flux:dropdown.item
                                wire:click="markAsRead({{ $notification->id }})"
                                :class="notification->read_at ? 'opacity-50' : ''"
                            >
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-sm
                                                {{ $notification->data['type'] === 'error' ? 'bg-red-100 text-red-500' : '' }}
                                                {{ $notification->data['type'] === 'success' ? 'bg-green-100 text-green-500' : '' }}
                                                {{ $notification->data['type'] === 'warning' ? 'bg-yellow-100 text-yellow-500' : '' }}
                                                {{ $notification->data['type'] === 'info' ? 'bg-blue-100 text-blue-500' : '' }}
                                                ">
                                            @if(isset($notification->data['type']) && in_array($notification->data['type'], ['error', 'success', 'warning', 'info']))
                                                <heroicon::{{ $notification->data['type'] }} class="h-4 w-4" />
                                            @else
                                                <heroicon::bell class="h-4 w-4" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ $notification->data['message'] }}</p>
                                        <p class="text-xs text-gray-500 truncate">
                                            {{ $notification->data['sender_name'] }} ·
                                            {{ \Illuminate\Support\Carbon::parse($notification->data['timestamp'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </flux:dropdown.item>
                        @empty
                            <flux:dropdown.item class="text-xs text-gray-500">
                                {{ __('No notifications') }}
                            </flux:dropdown.item>
                        @endforelse
                    </div>
                    
                    <flux:dropdown.divider />
                    
                    <flux:dropdown.item
                        href="{{ route('filament.app.pages.notifications') }}"
                        class="flex items-center justify-between text-sm"
                    >
                        <span>{{ __('View all notifications') }}</span>
                        <heroicon::chevron-right class="h-4 w-4" />
                    </flux:dropdown.item>
                </flux:dropdown.menu>
            </flux:dropdown>
            
            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('filament.app.pages.insights') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('filament.app.pages.insights')" :current="request()->routeIs('filament.app.pages.dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
