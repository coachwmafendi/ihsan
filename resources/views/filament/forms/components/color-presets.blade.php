<div
    x-data="{
        presets: [
            { name: 'Clean', text: '#212830', bg: '#FFFFFF', icon: '#FF435A', border: '#E5E7EB' },
            { name: 'Emerald', text: '#1F2937', bg: '#FFFFFF', icon: '#059669', border: '#D1FAE5' },
            { name: 'Warm', text: '#292524', bg: '#FFFBEB', icon: '#EA580C', border: '#FED7AA' },
            { name: 'Ocean', text: '#1E3A5F', bg: '#F0F9FF', icon: '#0284C7', border: '#BAE6FD' },
            { name: 'Royal', text: '#1E1B4B', bg: '#F5F3FF', icon: '#7C3AED', border: '#DDD6FE' },
        ],
        applyPreset(preset) {
            $wire.set('data.config.text_color', preset.text);
            $wire.set('data.config.background_color', preset.bg);
            $wire.set('data.config.icon_color', preset.icon);
            $wire.set('data.config.border_color', preset.border);
        }
    }"
    class="col-span-full"
>
    <p class="mb-2 text-xs font-medium text-zinc-500">Quick presets</p>
    <div class="flex flex-wrap gap-2">
        <template x-for="preset in presets" :key="preset.name">
            <button
                type="button"
                x-on:click="applyPreset(preset)"
                class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50"
            >
                <span class="flex -space-x-1">
                    <span class="inline-block size-3 rounded-full ring-1 ring-zinc-200" :style="'background-color: ' + preset.text"></span>
                    <span class="inline-block size-3 rounded-full ring-1 ring-zinc-200" :style="'background-color: ' + preset.bg"></span>
                    <span class="inline-block size-3 rounded-full ring-1 ring-zinc-200" :style="'background-color: ' + preset.icon"></span>
                </span>
                <span x-text="preset.name"></span>
            </button>
        </template>
    </div>
</div>
