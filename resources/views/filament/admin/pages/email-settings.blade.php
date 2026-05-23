<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
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

        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h3 class="text-sm font-semibold text-gray-900">SPF / DKIM / DMARC</h3>
            <p class="mt-1 text-sm text-gray-600">
                Untuk elak email masuk spam, pastikan DNS domain dah setup SPF, DKIM, dan DMARC records.
                Rujuk documentation mail provider untuk panduan.
            </p>
        </div>
    </div>
</x-filament-panels::page>
