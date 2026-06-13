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
                    <li class="font-medium text-slate-900">Profile</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>

    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button type="button"
                @click="tab = 'information'"
                :class="tab === 'information' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Information
            </button>
            <button type="button"
                @click="tab = 'contact'"
                :class="tab === 'contact' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Contact
            </button>
            <button type="button"
                @click="tab = 'address'"
                :class="tab === 'address' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Address
            </button>
            <button type="button"
                @click="tab = 'social'"
                :class="tab === 'social' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Social
            </button>
            <button type="button"
                @click="tab = 'bank'"
                :class="tab === 'bank' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Bank
            </button>
        </nav>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Information --}}
        <div x-show="tab === 'information'" x-cloak class="space-y-6">
            <x-ui.card title="Organisation Information" description="Basic details about your organisation.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700">Organisation Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" wire:model="name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="ros_rob_number" class="block text-sm font-medium text-slate-700">ROS / ROB Number</label>
                        <input type="text" id="ros_rob_number" wire:model="ros_rob_number" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea id="description" wire:model="description" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"></textarea>
                    </div>

                    <div>
                        <label for="website_url" class="block text-sm font-medium text-slate-700">Website URL</label>
                        <input type="url" id="website_url" wire:model="website_url" placeholder="https://example.org" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="logo" class="block text-sm font-medium text-slate-700">Logo</label>
                        <div class="mt-1 flex items-center gap-4">
                            @if ($existing_logo)
                                <img src="{{ Storage::url($existing_logo) }}" alt="Current logo" class="h-16 w-16 rounded-lg object-cover border border-slate-200">
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
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Portal Settings" description="Customise your public donation portal.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="portal_tagline" class="block text-sm font-medium text-slate-700">Portal Tagline</label>
                        <input type="text" id="portal_tagline" wire:model="portal_tagline" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="portal_reply_to_email" class="block text-sm font-medium text-slate-700">Reply-To Email</label>
                        <input type="email" id="portal_reply_to_email" wire:model="portal_reply_to_email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="portal_receipt_footer" class="block text-sm font-medium text-slate-700">Receipt Footer Text</label>
                        <textarea id="portal_receipt_footer" wire:model="portal_receipt_footer" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Allowed Email Domains</label>
                        <div class="mt-2 flex flex-wrap gap-2" x-data="{ newDomain: '' }">
                            @foreach ($allowed_domains as $index => $domain)
                                <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">
                                    {{ $domain }}
                                    <button type="button" wire:click="$set('allowed_domains', {{ json_encode(array_values(array_diff($allowed_domains, [$domain]))) }})" class="text-teal-600 hover:text-teal-800">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </span>
                            @endforeach
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="newDomain" @keydown.enter.prevent="
                                    if (newDomain.trim()) {
                                        $wire.set('allowed_domains', [...$wire.allowed_domains, newDomain.trim()]);
                                        newDomain = '';
                                    }
                                " placeholder="example.com" class="block w-40 rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                <button type="button" @click="
                                    if (newDomain.trim()) {
                                        $wire.set('allowed_domains', [...$wire.allowed_domains, newDomain.trim()]);
                                        newDomain = '';
                                    }
                                " class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Add</button>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Only email addresses from these domains can register as admins.</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Contact --}}
        <div x-show="tab === 'contact'" x-cloak class="space-y-6">
            <x-ui.card title="Contact Details" description="How supporters can reach your organisation.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-slate-700">Contact Email</label>
                        <input type="email" id="contact_email" wire:model="contact_email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @error('contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-slate-700">Contact Phone</label>
                        <input type="text" id="contact_phone" wire:model="contact_phone" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        @error('contact_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Address --}}
        <div x-show="tab === 'address'" x-cloak class="space-y-6">
            <x-ui.card title="Organisation Address" description="Your registered or primary operating address.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="address_line_1" class="block text-sm font-medium text-slate-700">Address Line 1</label>
                        <input type="text" id="address_line_1" wire:model="address_line_1" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address_line_2" class="block text-sm font-medium text-slate-700">Address Line 2</label>
                        <input type="text" id="address_line_2" wire:model="address_line_2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-slate-700">City</label>
                        <input type="text" id="city" wire:model="city" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="postcode" class="block text-sm font-medium text-slate-700">Postcode</label>
                        <input type="text" id="postcode" wire:model="postcode" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-slate-700">State</label>
                        <select id="state" wire:model="state" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                            <option value="">Select state</option>
                            <option value="Johor">Johor</option>
                            <option value="Kedah">Kedah</option>
                            <option value="Kelantan">Kelantan</option>
                            <option value="Melaka">Melaka</option>
                            <option value="Negeri Sembilan">Negeri Sembilan</option>
                            <option value="Pahang">Pahang</option>
                            <option value="Perak">Perak</option>
                            <option value="Perlis">Perlis</option>
                            <option value="Pulau Pinang">Pulau Pinang</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Sarawak">Sarawak</option>
                            <option value="Selangor">Selangor</option>
                            <option value="Terengganu">Terengganu</option>
                            <option value="Wilayah Persekutuan (Kuala Lumpur)">Wilayah Persekutuan (Kuala Lumpur)</option>
                            <option value="Wilayah Persekutuan (Labuan)">Wilayah Persekutuan (Labuan)</option>
                            <option value="Wilayah Persekutuan (Putrajaya)">Wilayah Persekutuan (Putrajaya)</option>
                        </select>
                    </div>

                    <div>
                        <label for="country" class="block text-sm font-medium text-slate-700">Country</label>
                        <select id="country" wire:model="country" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                            <option value="Malaysia">Malaysia</option>
                            <option value="Brunei">Brunei</option>
                            <option value="Cambodia">Cambodia</option>
                            <option value="Indonesia">Indonesia</option>
                            <option value="Myanmar">Myanmar</option>
                            <option value="Philippines">Philippines</option>
                            <option value="Singapore">Singapore</option>
                            <option value="Thailand">Thailand</option>
                            <option value="Vietnam">Vietnam</option>
                            <option value="Bangladesh">Bangladesh</option>
                            <option value="India">India</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="Sri Lanka">Sri Lanka</option>
                            <option value="Australia">Australia</option>
                            <option value="China">China</option>
                            <option value="Japan">Japan</option>
                            <option value="South Korea">South Korea</option>
                            <option value="Taiwan">Taiwan</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="United States">United States</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Social --}}
        <div x-show="tab === 'social'" x-cloak class="space-y-6">
            <x-ui.card title="Social Media" description="Link your organisation's social media accounts.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="social_facebook" class="block text-sm font-medium text-slate-700">Facebook</label>
                        <input type="url" id="social_facebook" wire:model="social_facebook" placeholder="https://facebook.com/..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="social_instagram" class="block text-sm font-medium text-slate-700">Instagram</label>
                        <input type="url" id="social_instagram" wire:model="social_instagram" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="social_twitter" class="block text-sm font-medium text-slate-700">Twitter / X</label>
                        <input type="url" id="social_twitter" wire:model="social_twitter" placeholder="https://x.com/..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="social_tiktok" class="block text-sm font-medium text-slate-700">TikTok</label>
                        <input type="url" id="social_tiktok" wire:model="social_tiktok" placeholder="https://tiktok.com/..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="social_youtube" class="block text-sm font-medium text-slate-700">YouTube</label>
                        <input type="url" id="social_youtube" wire:model="social_youtube" placeholder="https://youtube.com/..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Bank --}}
        <div x-show="tab === 'bank'" x-cloak class="space-y-6">
            <x-ui.card title="Bank Details" description="Bank account used for payouts.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="bank_name" class="block text-sm font-medium text-slate-700">Bank Name</label>
                        <input type="text" id="bank_name" wire:model="bank_name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="bank_account_name" class="block text-sm font-medium text-slate-700">Account Holder Name</label>
                        <input type="text" id="bank_account_name" wire:model="bank_account_name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="bank_account_number" class="block text-sm font-medium text-slate-700">Account Number</label>
                        <input type="text" id="bank_account_number" wire:model="bank_account_number" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-slate-500">These details are for reference only. Payouts are processed via Stripe Connect.</p>
                    </div>
                </x-slot:footer>
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
