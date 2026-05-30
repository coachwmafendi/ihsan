<x-filament-panels::page>
    <div class="ihsan-admin-page">
        <div class="rounded-lg border border-stone-200 bg-ihsan-cream p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex items-center gap-3">
                    <x-filament::button type="submit">
                        {{ __('Save') }}
                    </x-filament::button>
                </div>
            </form>
        </div>

        <x-admin.settings-panel title="Stripe API Mode">
            Current mode: <span class="font-medium">{{ $this->getApiMode() }}</span>
        </x-admin.settings-panel>
    </div>
</x-filament-panels::page>
