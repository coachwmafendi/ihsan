{{-- resources/views/components/app-shell.blade.php --}}
<div class="min-h-screen bg-[#f7f7fb] text-slate-900 flex">
    <x-sidebar />
    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
        <x-topbar />
        <main class="flex-1 p-6 md:p-8 overflow-y-auto">
            {{ $slot }}
        </main>
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</div>
