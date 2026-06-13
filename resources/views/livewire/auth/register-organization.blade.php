<div class="min-h-screen flex flex-col lg:flex-row">
    {{-- Left: Marketing / Dark side --}}
    <div class="hidden lg:flex lg:w-1/2 bg-[#0f172a] flex-col justify-center px-16 xl:px-24 relative overflow-hidden">
        {{-- Gradient blobs --}}
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] bg-teal-500/10 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[400px] h-[400px] bg-blue-500/10 rounded-full blur-[80px]"></div>

        <div class="relative z-10">
            <a href="{{ route('home') }}" class="text-white font-bold text-2xl tracking-tight mb-12 inline-block">Ihsan</a>

            <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight">
                Trusted by<br>leading nonprofits<br>worldwide
            </h1>

            <p class="mt-6 text-slate-400 text-lg max-w-md leading-relaxed">
                A giving platform that increases donation conversion and revenue — easily, professionally, and sustainably.
            </p>

            {{-- Testimonial --}}
            <div class="mt-12">
                <svg class="w-8 h-8 text-teal-500/40 mb-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                </svg>

                <p class="text-slate-300 text-base leading-relaxed max-w-lg">
                    Ihsan completely transformed how we handle donations. Not just the platform — it also comes with a team that helped us choose the best strategy for our masjid campaigns.
                </p>

                <div class="mt-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 font-bold text-lg">A</div>
                    <div>
                        <p class="text-white font-semibold text-sm uppercase tracking-wide">Ahmad Ibrahim</p>
                        <p class="text-slate-500 text-sm">Secretary, Masjid Al-Falah</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Form / Light side --}}
    <div class="w-full lg:w-1/2 bg-slate-50 flex items-center justify-center px-6 py-12 lg:py-0">
        <div class="w-full max-w-2xl">
            {{-- Mobile logo (shown only on small screens) --}}
            <div class="lg:hidden text-center mb-8">
                <a href="{{ route('home') }}" class="text-slate-900 font-bold text-2xl tracking-tight">Ihsan</a>
            </div>

            @if ($submitted)
                {{-- Success state --}}
                <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm text-center">
                    <div class="w-14 h-14 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-7 h-7 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">Application Received</h2>

                    <div class="mt-4 space-y-3 text-sm text-slate-600 leading-relaxed text-left">
                        <p>
                            Thank you for registering your organization with <strong>Ihsan</strong>. We have received your application and it is now being processed.
                        </p>

                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 my-4">
                            <h3 class="font-semibold text-slate-800 mb-2">What happens next?</h3>
                            <ul class="space-y-2 text-slate-600">
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-teal-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Our team will review your organization details within <strong>1-3 business days</strong>.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-teal-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                    <span>You will receive a confirmation email at <strong>{{ $contact_email }}</strong> once approved.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-teal-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    <span>After approval, you can create your admin account and start receiving donations immediately.</span>
                                </li>
                            </ul>
                        </div>

                        <p class="text-slate-500 text-xs">
                            If you have any questions, please contact us at <a href="mailto:support@getihsan.my" class="text-teal-600 hover:text-teal-700">support@getihsan.my</a>
                        </p>
                    </div>

                    <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-teal-600 hover:text-teal-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Back to homepage
                    </a>
                </div>
            @else
                {{-- Form --}}
                <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                    <h1 class="text-2xl font-bold text-slate-900 mb-1">Register Organization</h1>
                    <p class="text-sm text-slate-500 mb-8">Fill in your organization details to get started.</p>

                    <form wire:submit="submit" class="space-y-5">
                        {{-- Organization Name — full width --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">Organization Name</label>
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                placeholder="Masjid Al-Falah"
                                required
                                autofocus
                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                            @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Registration Type | ROS/ROB Number — 2 col --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="registration_type" class="block text-sm font-semibold text-slate-700 mb-1.5">Registration Type</label>
                                <select
                                    id="registration_type"
                                    wire:model="registration_type"
                                    required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                    <option value="" disabled>-- Select type --</option>
                                    <option value="ROS">ROS (Society)</option>
                                    <option value="ROB">ROB (Company)</option>
                                    <option value="Others">Others</option>
                                </select>
                                @error('registration_type')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="ros_rob_number" class="block text-sm font-semibold text-slate-700 mb-1.5">ROB/ROS Number</label>
                                <input
                                    type="text"
                                    id="ros_rob_number"
                                    wire:model="ros_rob_number"
                                    placeholder="PPM-001-10-01012020"
                                    required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                @error('ros_rob_number')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Sector — full width --}}
                        <div>
                            <label for="sector" class="block text-sm font-semibold text-slate-700 mb-1.5">Sector</label>
                            <select
                                id="sector"
                                wire:model="sector"
                                required
                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
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
                            @error('sector')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Email | Website — 2 col --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="contact_email" class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                                <input
                                    type="email"
                                    id="contact_email"
                                    wire:model="contact_email"
                                    placeholder="admin@organization.org"
                                    required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                @error('contact_email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="website_url" class="block text-sm font-semibold text-slate-700 mb-1.5">Website</label>
                                <input
                                    type="url"
                                    id="website_url"
                                    wire:model="website_url"
                                    placeholder="https://organization.org"
                                    required
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                >
                                @error('website_url')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Facebook URL — full width --}}
                        <div>
                            <label for="facebook_url" class="block text-sm font-semibold text-slate-700 mb-1.5">Facebook URL <span class="text-slate-400 font-normal">(optional)</span></label>
                            <input
                                type="url"
                                id="facebook_url"
                                wire:model="facebook_url"
                                placeholder="https://facebook.com/organization"
                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            >
                            @error('facebook_url')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex justify-center pt-2">
                            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-8 py-2.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                Submit Application
                            </button>
                        </div>
                    </form>
                </div>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-teal-600 hover:text-teal-700 transition-colors">Log in</a>
                </p>
            @endif
        </div>
    </div>
</div>
