<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-8">
            {{ $this->form }}

            <div class="border-t border-gray-200 pt-6">
                <x-filament::button
                    wire:click="sendNotification"
                    wire:loading.label="Sending..."
                    icon="heroicon-o-paper-airplane"
                >
                    Send Notification
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
