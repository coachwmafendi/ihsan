<div class="min-h-screen flex items-center justify-center py-20 px-4">
    <div class="w-full max-w-lg">
        {{-- Header --}}
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="text-white font-bold text-2xl tracking-tight">Ihsan</a>
            <h1 class="mt-6 text-2xl font-bold text-white">Register Organization</h1>
            <p class="mt-2 text-sm text-slate-400">Fill in your organization details to register.</p>
        </div>

        @if ($submitted)
            {{-- Success state --}}
            <div class="bg-teal-500/10 border border-teal-500/30 rounded-2xl p-8 text-center">
                <div class="w-12 h-12 bg-teal-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-white">Application Received</h2>
                <p class="mt-2 text-sm text-slate-400 leading-relaxed">
                    Thank you! Your application has been received. Our team will review it and contact you shortly.
                </p>
                <a href="{{ route('home') }}" class="mt-6 inline-block text-sm text-teal-400 hover:text-teal-300 transition-colors">
                    Back to homepage
                </a>
            </div>
        @else
            {{-- Form --}}
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-8">
                <form wire:submit="submit" class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-300 mb-1.5">Organization Name</label>
                        <input
                            type="text"
                            id="name"
                            wire:model="name"
                            placeholder="Masjid Al-Falah"
                            required
                            autofocus
                            class="w-full bg-slate-900/80 border border-slate-600 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="registration_type" class="block text-sm font-medium text-slate-300 mb-1.5">Registration Type</label>
                        <select
                            id="registration_type"
                            wire:model="registration_type"
                            required
                            class="w-full bg-slate-900/80 border border-slate-600 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                            <option value="" disabled>-- Select type --</option>
                            <option value="ROS">ROS (Society)</option>
                            <option value="ROB">ROB (Company)</option>
<option value="Others">Others</option>
                        </select>
                        @error('registration_type')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="ros_rob_number" class="block text-sm font-medium text-slate-300 mb-1.5">ROB/ROS Number</label>
                        <input
                            type="text"
                            id="ros_rob_number"
                            wire:model="ros_rob_number"
                            placeholder="PPM-001-10-01012020"
                            required
                            class="w-full bg-slate-900/80 border border-slate-600 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                        @error('ros_rob_number')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="sector" class="block text-sm font-medium text-slate-300 mb-1.5">Sector</label>
                        <select
                            id="sector"
                            wire:model="sector"
                            required
                            class="w-full bg-slate-900/80 border border-slate-600 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                            <option value="" disabled>-- Select sector --</option>
                            <option value="Agama">Religion</option>
                            <option value="Pendidikan">Education</option>
                            <option value="Kebajikan Sosial">Social Welfare</option>
                            <option value="Kesihatan">Health</option>
                            <option value="Alam Sekitar">Environment</option>
                            <option value="Sukan & Rekreasi">Sports & Recreation</option>
                            <option value="Lain-lain">Others</option>
                        </select>
                        @error('sector')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                        <input
                            type="email"
                            id="contact_email"
                            wire:model="contact_email"
                            placeholder="admin@organization.org"
                            required
                            class="w-full bg-slate-900/80 border border-slate-600 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                        @error('contact_email')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="website_url" class="block text-sm font-medium text-slate-300 mb-1.5">Website</label>
                        <input
                            type="url"
                            id="website_url"
                            wire:model="website_url"
                            placeholder="https://organization.org"
                            required
                            class="w-full bg-slate-900/80 border border-slate-600 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                        @error('website_url')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="facebook_url" class="block text-sm font-medium text-slate-300 mb-1.5">Facebook URL <span class="text-slate-500 font-normal">(optional)</span></label>
                        <input
                            type="url"
                            id="facebook_url"
                            wire:model="facebook_url"
                            placeholder="https://facebook.com/organization"
                            class="w-full bg-slate-900/80 border border-slate-600 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                        @error('facebook_url')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-semibold py-2.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                        Submit Application
                    </button>
                </form>
            </div>

            <p class="mt-4 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="text-teal-400 hover:text-teal-300 transition-colors">Log in</a>
            </p>
        @endif
    </div>
</div>
