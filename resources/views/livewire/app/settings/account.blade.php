<div class="space-y-6">
    <x-ui.page-header title="Settings">
        <x-slot:subtitle>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-slate-500">
                    <li>Settings</li>
                    <li>
                        <svg class="mx-1 h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="font-medium text-slate-900">Account</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>

    <form wire:submit="save" class="space-y-6">
        <x-ui.card title="Account Details" description="Update your name, email address and profile picture.">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Profile Picture</label>
                    <div class="mt-2 flex items-center gap-4">
                        @if ($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" alt="New avatar preview" class="h-16 w-16 rounded-full object-cover border border-slate-200">
                        @elseif ($existing_avatar)
                            <img src="{{ Storage::disk('public')->url($existing_avatar) }}" alt="Current avatar" class="h-16 w-16 rounded-full object-cover border border-slate-200">
                        @else
                            <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 border border-slate-200">
                                <span class="text-lg font-semibold">{{ auth()->user()?->initials() }}</span>
                            </div>
                        @endif

                        <div class="flex flex-col gap-2">
                            <input type="file" id="avatar" wire:model="avatar" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100">
                            @if ($existing_avatar)
                                <button type="button" wire:click="removeAvatar" class="self-start text-sm font-medium text-red-600 hover:text-red-500">
                                    Remove picture
                                </button>
                            @endif
                            <div wire:loading wire:target="avatar" class="text-xs text-slate-500">Uploading...</div>
                            @error('avatar') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                    <input type="text" id="name" wire:model="name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        @if (! auth()->user()?->isSuperAdmin()) disabled @endif
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 disabled:opacity-60"
                    >
                    @if (! auth()->user()?->isSuperAdmin())
                        <p class="mt-1 text-xs text-slate-500">Organization admin email cannot be changed.</p>
                    @endif
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Change Password" description="Leave blank if you do not want to change your password.">
            <div class="grid gap-6 max-w-lg">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700">Current Password</label>
                    <input type="password" id="current_password" wire:model="current_password" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
                    <input type="password" id="password" wire:model="password" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm New Password</label>
                    <input type="password" id="password_confirmation" wire:model="password_confirmation" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end">
            <x-ui.button type="submit" variant="primary" size="lg">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving...</span>
            </x-ui.button>
        </div>
    </form>
</div>
