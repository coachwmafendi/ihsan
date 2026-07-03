{{-- resources/views/livewire/app/command-palette.blade.php --}}
<div
    x-data="{
        query: '',
        highlighted: 0,
        pages: @js($pages),
        actions: @js($actions),
        get filteredPages() {
            return this.pages.filter(p => p.label.toLowerCase().includes(this.query.toLowerCase()));
        },
        get filteredActions() {
            return this.actions.filter(a => a.label.toLowerCase().includes(this.query.toLowerCase()));
        },
        get combined() {
            return [...this.filteredPages, ...this.filteredActions];
        },
        go(url) {
            window.location.href = url;
        },
        handleKeydown(e) {
            if (this.query === '' && (e.key.toLowerCase() === 'd' || e.key.toLowerCase() === 'k')) {
                e.preventDefault();
                const action = this.actions.find(a => a.hotkey.toLowerCase() === e.key.toLowerCase());
                if (action) { this.go(action.url); }
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlighted = Math.min(this.highlighted + 1, this.combined.length - 1);
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlighted = Math.max(this.highlighted - 1, 0);
            }
            if (e.key === 'Enter') {
                const item = this.combined[this.highlighted];
                if (item) { this.go(item.url); }
            }
        },
    }"
    x-init="$watch('query', () => highlighted = 0)"
>
    <flux:modal wire:model="open" name="command-palette" class="max-w-xl" @close="$wire.closePalette()">
        <div class="-m-4">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                <x-heroicon-o-magnifying-glass class="size-5 text-slate-400 shrink-0" />
                <input
                    type="text"
                    x-model="query"
                    x-ref="searchInput"
                    x-on:keydown="handleKeydown($event)"
                    x-effect="if ($wire.open) $nextTick(() => $refs.searchInput.focus())"
                    placeholder="Search pages, donors, campaigns..."
                    class="flex-1 border-0 focus:ring-0 text-sm placeholder:text-slate-400"
                    autocomplete="off"
                >
                <kbd class="text-xs text-slate-400 border border-slate-200 rounded px-1.5 py-0.5">Esc</kbd>
            </div>

            <div class="max-h-96 overflow-y-auto py-2">
                <template x-if="filteredPages.length > 0">
                    <div>
                        <p class="px-4 pt-2 pb-1 text-xs font-medium text-slate-400">PAGES</p>
                        <template x-for="(page, index) in filteredPages" :key="page.label">
                            <button
                                type="button"
                                x-on:click="go(page.url)"
                                :class="combined.indexOf(page) === highlighted ? 'bg-slate-100' : ''"
                                class="w-full text-left px-4 py-2.5 text-sm text-slate-900 hover:bg-slate-50"
                                x-text="page.label"
                            ></button>
                        </template>
                    </div>
                </template>

                <template x-if="filteredActions.length > 0">
                    <div>
                        <p class="px-4 pt-3 pb-1 text-xs font-medium text-slate-400">ACTIONS</p>
                        <template x-for="action in filteredActions" :key="action.label">
                            <button
                                type="button"
                                x-on:click="go(action.url)"
                                :class="combined.indexOf(action) === highlighted ? 'bg-slate-100' : ''"
                                class="w-full text-left px-4 py-2.5 text-sm text-slate-900 hover:bg-slate-50 flex items-center justify-between"
                            >
                                <span x-text="action.label"></span>
                                <kbd
                                    x-show="query === ''"
                                    x-text="action.hotkey"
                                    class="text-xs text-slate-400 border border-slate-200 rounded px-1.5 py-0.5"
                                ></kbd>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </flux:modal>
</div>
