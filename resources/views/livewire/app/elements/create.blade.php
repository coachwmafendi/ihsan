{{-- resources/views/livewire/app/elements/create.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('app.elements.index') }}" wire:navigate class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back
        </a>
    </div>

    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Create Element</h1>
        <p class="mt-1 text-sm text-slate-500">Add a new embeddable element to your campaign</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Info --}}
        <x-ui.card title="Basic Information">
            <div class="space-y-4">
                <div>
                    <label for="campaign_id" class="block text-sm font-medium text-slate-700">Campaign <span class="text-red-500">*</span></label>
                    <select
                        id="campaign_id"
                        wire:model="campaign_id"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                        <option value="">Select a campaign</option>
                        @foreach ($this->campaigns as $campaign)
                            <option value="{{ $campaign->id }}">{{ $campaign->title }}</option>
                        @endforeach
                    </select>
                    @error('campaign_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700">Type <span class="text-red-500">*</span></label>
                    <select
                        id="type"
                        wire:model="type"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                        <option value="button">Button</option>
                        <option value="floating_button">Floating Button</option>
                        <option value="form">Form</option>
                        <option value="popup">Popup</option>
                        <option value="link">Link</option>
                        <option value="sticky_button">Sticky Button</option>
                    </select>
                    @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Ramadan Donation Button"
                    />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Active</h3>
                        <p class="text-xs text-slate-500">Make this element visible and usable</p>
                    </div>
                    <button
                        type="button"
                        wire:click="$toggle('is_active')"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 {{ $is_active ? 'bg-teal-600' : 'bg-slate-200' }}"
                        role="switch"
                        aria-checked="{{ $is_active ? 'true' : 'false' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>
        </x-ui.card>

        {{-- Configuration --}}
        <x-ui.card title="Configuration" description="Optional display settings">
            <div class="space-y-4">
                <div>
                    <label for="config_title" class="block text-sm font-medium text-slate-700">Title</label>
                    <input
                        type="text"
                        id="config_title"
                        wire:model="config_title"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Support Our Cause"
                    />
                </div>

                <div>
                    <label for="config_message" class="block text-sm font-medium text-slate-700">Message</label>
                    <textarea
                        id="config_message"
                        wire:model="config_message"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="Enter a message to display..."
                    ></textarea>
                </div>

                <div>
                    <label for="config_button_text" class="block text-sm font-medium text-slate-700">Button Text</label>
                    <input
                        type="text"
                        id="config_button_text"
                        wire:model="config_button_text"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Donate Now"
                    />
                </div>
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <x-ui.button href="{{ route('app.elements.index') }}" variant="ghost">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary">Create Element</x-ui.button>
        </div>
    </form>
</div>
