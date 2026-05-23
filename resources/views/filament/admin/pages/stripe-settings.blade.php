<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex items-center gap-3">
                    <x-filament::button type="submit">
                        {{ __('Save') }}
                    </x-filament::button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white p-6 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Stripe API Mode</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Current mode: <span class="font-medium">{{ $this->getApiMode() }}</span>
            </p>
        </div>
    </div>
</x-filament-panels::page>
