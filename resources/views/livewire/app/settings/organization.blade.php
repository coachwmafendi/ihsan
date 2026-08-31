<div x-data="{ tab: @entangle('activeTab') }" class="space-y-6">
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
                    <li class="font-medium text-slate-900">Organization</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>

    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 overflow-x-auto sm:space-x-8" aria-label="Tabs">
            <button type="button"
                @click="tab = 'information'"
                :class="tab === 'information' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Information
            </button>
            <button type="button"
                @click="tab = 'contact'"
                :class="tab === 'contact' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Contact
            </button>
            <button type="button"
                @click="tab = 'address'"
                :class="tab === 'address' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Address
            </button>
            <button type="button"
                @click="tab = 'social'"
                :class="tab === 'social' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Social
            </button>
        </nav>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Information --}}
        <div x-show="tab === 'information'" x-cloak class="space-y-6">
            <x-ui.card title="Organization Information" description="Basic details about your organization.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700">Organization Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" wire:model="name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="ros_rob_number" class="block text-sm font-medium text-slate-700">ROS / ROB Number</label>
                        <input type="text" id="ros_rob_number" wire:model="ros_rob_number" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea id="description" wire:model="description" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                    </div>

                    <div>
                        <label for="website_url" class="block text-sm font-medium text-slate-700">Website URL</label>
                        <input type="url" id="website_url" wire:model="website_url" placeholder="https://example.org" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="logo" class="block text-sm font-medium text-slate-700">Logo</label>
                        <div class="mt-1 flex items-center gap-4">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="New logo preview" class="h-16 w-16 rounded-lg object-cover border border-slate-200">
                            @elseif ($existing_logo)
                                <img src="{{ Storage::disk('public')->url($existing_logo) }}" alt="Current logo" class="h-16 w-16 rounded-lg object-cover border border-slate-200">
                            @else
                                <div class="h-16 w-16 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            <input type="file" id="logo" wire:model="logo" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100">
                        </div>
                        <div wire:loading wire:target="logo" class="mt-2 text-sm text-slate-500">Uploading...</div>
                        @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-ui.card>

        </div>

        {{-- Contact --}}
        <div x-show="tab === 'contact'" x-cloak class="space-y-6">
            <x-ui.card title="Contact Details" description="How supporters can reach your organization.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-slate-700">Contact Email</label>
                        <input type="email" id="contact_email" wire:model="contact_email" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-slate-700">Contact Phone</label>
                        <input type="text" id="contact_phone" wire:model="contact_phone" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('contact_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Address --}}
        <div x-show="tab === 'address'" x-cloak class="space-y-6">
            <x-ui.card title="Organization Address" description="Your registered or primary operating address.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="address_line_1" class="block text-sm font-medium text-slate-700">Address Line 1</label>
                        <input type="text" id="address_line_1" wire:model="address_line_1" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address_line_2" class="block text-sm font-medium text-slate-700">Address Line 2</label>
                        <input type="text" id="address_line_2" wire:model="address_line_2" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-slate-700">City</label>
                        <input type="text" id="city" wire:model="city" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="postcode" class="block text-sm font-medium text-slate-700">Postcode</label>
                        <input type="text" id="postcode" wire:model="postcode" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-slate-700">State</label>

                        <div x-show="$wire.country === 'Malaysia'" x-cloak>
                            <x-ui.select id="state" wire:model="state" class="mt-1 block w-full">
                                <flux:select.option value="">Select state</flux:select.option>
                                <flux:select.option value="Johor">Johor</flux:select.option>
                                <flux:select.option value="Kedah">Kedah</flux:select.option>
                                <flux:select.option value="Kelantan">Kelantan</flux:select.option>
                                <flux:select.option value="Melaka">Melaka</flux:select.option>
                                <flux:select.option value="Negeri Sembilan">Negeri Sembilan</flux:select.option>
                                <flux:select.option value="Pahang">Pahang</flux:select.option>
                                <flux:select.option value="Perak">Perak</flux:select.option>
                                <flux:select.option value="Perlis">Perlis</flux:select.option>
                                <flux:select.option value="Pulau Pinang">Pulau Pinang</flux:select.option>
                                <flux:select.option value="Sabah">Sabah</flux:select.option>
                                <flux:select.option value="Sarawak">Sarawak</flux:select.option>
                                <flux:select.option value="Selangor">Selangor</flux:select.option>
                                <flux:select.option value="Terengganu">Terengganu</flux:select.option>
                                <flux:select.option value="Wilayah Persekutuan (Kuala Lumpur)">Wilayah Persekutuan (Kuala Lumpur)</flux:select.option>
                                <flux:select.option value="Wilayah Persekutuan (Labuan)">Wilayah Persekutuan (Labuan)</flux:select.option>
                                <flux:select.option value="Wilayah Persekutuan (Putrajaya)">Wilayah Persekutuan (Putrajaya)</flux:select.option>
                            </x-ui.select>
                        </div>

                        <div x-show="$wire.country !== 'Malaysia'" x-cloak>
                            <input type="text" id="state_free" wire:model="state" placeholder="State / Province / Region" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                    </div>

                    <div>
                        <label for="country" class="block text-sm font-medium text-slate-700">Country</label>
                        <x-ui.select id="country" wire:model="country" class="mt-1 block w-full">
                            <flux:select.option value="Malaysia">Malaysia</flux:select.option>
                            <flux:select.option value="Brunei">Brunei</flux:select.option>
                            <flux:select.option value="Cambodia">Cambodia</flux:select.option>
                            <flux:select.option value="Indonesia">Indonesia</flux:select.option>
                            <flux:select.option value="Myanmar">Myanmar</flux:select.option>
                            <flux:select.option value="Philippines">Philippines</flux:select.option>
                            <flux:select.option value="Singapore">Singapore</flux:select.option>
                            <flux:select.option value="Thailand">Thailand</flux:select.option>
                            <flux:select.option value="Vietnam">Vietnam</flux:select.option>
                            <flux:select.option value="Bangladesh">Bangladesh</flux:select.option>
                            <flux:select.option value="India">India</flux:select.option>
                            <flux:select.option value="Pakistan">Pakistan</flux:select.option>
                            <flux:select.option value="Sri Lanka">Sri Lanka</flux:select.option>
                            <flux:select.option value="Australia">Australia</flux:select.option>
                            <flux:select.option value="China">China</flux:select.option>
                            <flux:select.option value="Japan">Japan</flux:select.option>
                            <flux:select.option value="South Korea">South Korea</flux:select.option>
                            <flux:select.option value="Taiwan">Taiwan</flux:select.option>
                            <flux:select.option value="United Kingdom">United Kingdom</flux:select.option>
                            <flux:select.option value="United States">United States</flux:select.option>
                            <flux:select.option value="Other">Other</flux:select.option>
                        </x-ui.select>
                    </div>

                    <div>
                        <label for="timezone" class="block text-sm font-medium text-slate-700">Reporting timezone</label>
                        <x-ui.select id="timezone" wire:model="timezone" class="mt-1 block w-full">
                            @foreach ($this->timezoneOptions() as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </x-ui.select>
                        <p class="mt-1 text-xs text-slate-500">
                            Decides where a day starts and ends on your dashboard, reports and exports.
                        </p>
                        @error('timezone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Social --}}
        <div x-show="tab === 'social'" x-cloak class="space-y-6">
            <x-ui.card title="Social Media" description="Link your organization's social media accounts.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="social_facebook" class="block text-sm font-medium text-slate-700">Facebook</label>
                        <input type="url" id="social_facebook" wire:model="social_facebook" placeholder="https://facebook.com/..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="social_instagram" class="block text-sm font-medium text-slate-700">Instagram</label>
                        <input type="url" id="social_instagram" wire:model="social_instagram" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="social_twitter" class="block text-sm font-medium text-slate-700">Twitter / X</label>
                        <input type="url" id="social_twitter" wire:model="social_twitter" placeholder="https://x.com/..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="social_tiktok" class="block text-sm font-medium text-slate-700">TikTok</label>
                        <input type="url" id="social_tiktok" wire:model="social_tiktok" placeholder="https://tiktok.com/..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="social_youtube" class="block text-sm font-medium text-slate-700">YouTube</label>
                        <input type="url" id="social_youtube" wire:model="social_youtube" placeholder="https://youtube.com/..." class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Save --}}
        <div class="flex items-center justify-end">
            <x-ui.button type="submit" variant="primary" size="lg">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving...</span>
            </x-ui.button>
        </div>
    </form>
</div>
