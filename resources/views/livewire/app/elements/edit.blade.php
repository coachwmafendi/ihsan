{{-- resources/views/livewire/app/elements/edit.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('app.elements.index') }}" wire:navigate class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            <x-heroicon-o-arrow-left class="mr-1 size-4" />
            Back
        </a>
    </div>

    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Edit Element</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $element->name }}</p>
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
                    <label class="block text-sm font-medium text-slate-700">Type</label>
                    <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        {{ ucwords(str_replace('_', ' ', $element->type->value)) }}
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Type cannot be changed after creation.</p>
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
        <x-ui.card title="Configuration">
            <div class="space-y-4">
                <div>
                    <label for="config_title" class="block text-sm font-medium text-slate-700">Title</label>
                    <input
                        type="text"
                        id="config_title"
                        wire:model="config_title"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Support our cause"
                    />
                </div>

                <div>
                    <label for="config_message" class="block text-sm font-medium text-slate-700">Message</label>
                    <textarea
                        id="config_message"
                        wire:model="config_message"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="Short description shown to donors..."
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

        {{-- Embed Code --}}
        <x-ui.card title="Embed Code" description="Copy this code to embed on your website">
            <div class="space-y-3">
                <div class="relative">
                    <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-300"><code>&lt;script src="{{ url('/e/widget.js') }}" data-token="{{ $element->token }}" data-type="{{ $element->type->value }}" async&gt;&lt;/script&gt;</code></pre>
                    <button
                        type="button"
                        onclick="navigator.clipboard.writeText(`<script src='{{ url('/e/widget.js') }}' data-token='{{ $element->token }}' data-type='{{ $element->type->value }}' async></script>`)"
                        class="absolute right-2 top-2 rounded-md bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-600"
                    >
                        Copy
                    </button>
                </div>
                <p class="text-xs text-slate-500">Token: {{ $element->token }}</p>
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <x-ui.button wireClick="delete" variant="danger" onclick="return confirm('Are you sure you want to delete this element?')">Delete Element</x-ui.button>
            <div class="flex items-center gap-3">
                <x-ui.button href="{{ route('app.elements.index') }}" variant="ghost">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
            </div>
        </div>
    </form>
</div>
