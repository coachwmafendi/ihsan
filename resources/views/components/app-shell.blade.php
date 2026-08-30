{{-- resources/views/components/app-shell.blade.php --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            collapsed: JSON.parse(localStorage.getItem('sidebarCollapsed') ?? 'false'),

            toggle() {
                this.collapsed = ! this.collapsed;
                localStorage.setItem('sidebarCollapsed', JSON.stringify(this.collapsed));
            },
        });
    });


</script>

<div
    x-data
    x-init="document.getElementById('sidebar-prehydrate')?.remove()"
    x-on:keydown.window="(e) => {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $dispatch('open-command-palette');
        }
    }"
    class="min-h-screen bg-[#f7f7fb] text-slate-900 flex"
>
    {{-- Safelist dynamic Tailwind width/padding classes for the collapsible sidebar --}}
    <div class="hidden lg:w-16 lg:w-64 lg:pl-16 lg:pl-64"></div>

    <x-sidebar />
    <div
        id="app-content"
        {{-- Arm the padding transition only after hydration, so the prehydrate handoff does not animate. --}}
        x-data="{ animate: false }"
        x-init="requestAnimationFrame(function () { requestAnimationFrame(function () { animate = true }) })"
        class="flex-1 flex flex-col min-w-0"
        :class="[
            $store.sidebar.collapsed ? 'lg:pl-16' : 'lg:pl-64',
            animate ? 'transition-[padding] duration-300 ease-in-out motion-reduce:transition-none' : '',
        ]"
    >
        <livewire:app.topbar />
        <main class="flex-1 p-6 md:p-8">
            {{ $slot }}
        </main>
    </div>

    <livewire:app.command-palette />
</div>
