<x-filament-panels::page>
    <div class="ihsan-admin-page">
        <div class="rounded-lg border border-stone-200 bg-ihsan-cream p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex items-center gap-3">
                    <x-filament::button type="submit">
                        {{ __('Save') }}
                    </x-filament::button>

                    <x-filament::button color="gray" wire:click="sendTestEmail">
                        {{ __('Send Test Email') }}
                    </x-filament::button>
                </div>
            </form>
        </div>

        <x-admin.settings-panel title="SPF / DKIM / DMARC">
            To prevent emails from landing in spam, make sure your domain DNS has SPF, DKIM, and DMARC records configured.
            Refer to your mail provider's documentation for guidance.
        </x-admin.settings-panel>
    </div>
</x-filament-panels::page>
