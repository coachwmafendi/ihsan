<div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-5">
    <x-filament::button
        :href="$cancelUrl ?? '#'"
        color="gray"
        tag="a"
    >
        Cancel
    </x-filament::button>

    <x-filament::button type="submit">
        {{ $label ?? 'Save changes' }}
    </x-filament::button>
</div>
