{{-- resources/views/livewire/app/command-palette.blade.php --}}
<div
    x-data="{
        highlighted: 0,
        lastQuery: $wire.query,
        go(url) {
            window.location.href = url;
        },
        applyHighlight() {
            const items = this.$el.querySelectorAll('[data-palette-item]');
            if ($wire.query !== this.lastQuery) {
                this.highlighted = 0;
                this.lastQuery = $wire.query;
            }
            this.highlighted = Math.min(this.highlighted, Math.max(items.length - 1, 0));
            items.forEach((el, i) => el.classList.toggle('bg-slate-100', i === this.highlighted));
        },
        handleKeydown(e) {
            const items = this.$el.querySelectorAll('[data-palette-item]');
            if ($wire.query === '') {
                const action = Array.from(items).find(el => el.dataset.hotkey?.toLowerCase() === e.key.toLowerCase());
                if (action) {
                    e.preventDefault();
                    this.go(action.dataset.url);
                    return;
                }
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlighted = Math.min(this.highlighted + 1, items.length - 1);
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlighted = Math.max(this.highlighted - 1, 0);
            }
            if (e.key === 'Enter') {
                const item = items[this.highlighted];
                if (item) { this.go(item.dataset.url); }
            }
        },
    }"
    x-effect="$wire.query; applyHighlight();"
    x-init="applyHighlight()"
>
    <flux:modal wire:model="open" name="command-palette" class="w-full max-w-2xl min-w-xl" @close="$wire.closePalette()">
        <div class="-m-4">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
                <x-heroicon-o-magnifying-glass class="size-6 text-slate-400 shrink-0" />
                <flux:input
                    wire:model.live.debounce.300ms="query"
                    x-ref="searchInput"
                    x-on:keydown="handleKeydown($event)"
                    x-effect="if ($wire.open) $nextTick(() => $refs.searchInput.focus())"
                    placeholder="Search pages, donors, campaigns..."
                    class="flex-1"
                    class:input="border-0 shadow-none bg-transparent focus:ring-0 text-base placeholder:text-slate-400"
                    autocomplete="off"
                />
                <kbd class="text-xs text-slate-400 border border-slate-200 rounded px-1.5 py-0.5">Esc</kbd>
            </div>

            <div class="max-h-96 overflow-y-auto py-2">
                @if (count($pages) > 0)
                    <div>
                        <p class="px-4 pt-2 pb-1 text-xs font-medium text-slate-400">PAGES</p>
                        @foreach ($pages as $page)
                            <button
                                type="button"
                                data-palette-item
                                data-url="{{ $page['url'] }}"
                                x-on:click="go($el.dataset.url)"
                                class="w-full text-left px-4 py-2.5 text-sm text-slate-900 hover:bg-slate-50"
                            >
                                {{ $page['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @if (count($actions) > 0)
                    <div>
                        <p class="px-4 pt-3 pb-1 text-xs font-medium text-slate-400">ACTIONS</p>
                        @foreach ($actions as $action)
                            <button
                                type="button"
                                data-palette-item
                                data-url="{{ $action['url'] }}"
                                data-hotkey="{{ $action['hotkey'] }}"
                                x-on:click="go($el.dataset.url)"
                                class="w-full text-left px-4 py-2.5 text-sm text-slate-900 hover:bg-slate-50 flex items-center justify-between"
                            >
                                <span>{{ $action['label'] }}</span>
                                @if ($query === '')
                                    <kbd class="text-xs text-slate-400 border border-slate-200 rounded px-1.5 py-0.5">{{ $action['hotkey'] }}</kbd>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                @if (count($supporters) > 0)
                    <div>
                        <p class="px-4 pt-3 pb-1 text-xs font-medium text-slate-400">SUPPORTERS</p>
                        @foreach ($supporters as $supporter)
                            <button
                                type="button"
                                data-palette-item
                                data-url="{{ $supporter['url'] }}"
                                x-on:click="go($el.dataset.url)"
                                class="w-full text-left px-4 py-2.5 text-sm text-slate-900 hover:bg-slate-50"
                            >
                                <span>{{ $supporter['label'] }}</span>
                                <span class="block text-xs text-slate-500">{{ $supporter['sublabel'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($query !== '' && ! $hasResults)
                    <div class="px-4 py-8 text-center text-sm text-slate-500">
                        {{ app()->getLocale() === 'ms' ? 'Tiada padanan untuk' : 'No matches for' }} "{{ $query }}".
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
