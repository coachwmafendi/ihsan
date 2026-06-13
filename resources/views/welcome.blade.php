<x-layouts::landing>
    <x-slot:title>@lang('site.title')</x-slot:title>

    {{-- Nav --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-[#0f172a]/80 backdrop-blur-lg border-b border-white/5" x-data="{ open: false }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-white font-bold text-lg tracking-tight">Ihsan</a>

            <div class="hidden md:flex items-center gap-6 text-sm">
                <a href="#features" class="text-slate-400 hover:text-white transition-colors">@lang('nav.features')</a>
                <a href="#pricing" class="text-slate-400 hover:text-white transition-colors">@lang('nav.pricing')</a>
                <a href="#faq" class="text-slate-400 hover:text-white transition-colors">@lang('nav.faq')</a>

                @auth
                    <a href="{{ route('app.insights') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-5 py-2 rounded-full font-semibold text-sm transition-colors">Dashboard</a>
                @else
                    <a href="{{ route('register.org') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-5 py-2 rounded-full font-semibold text-sm transition-colors">Register Organization</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-slate-300 hover:text-white transition-colors">@lang('nav.register')</a>
                    @endif
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white transition-colors">@lang('nav.login')</a>
                @endauth

                <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-300 transition-colors border border-white/10 rounded-full px-3 py-1.5">
                    @lang('nav.switch_language')
                </a>
            </div>

            {{-- Mobile nav --}}
            <div class="flex md:hidden items-center gap-3">
                <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-xs font-medium text-slate-500 border border-white/10 rounded-full px-3 py-1.5">
                    @lang('nav.switch_language')
                </a>
                <button @click="open = !open" class="p-2 text-slate-400 hover:text-white transition-colors" aria-label="Menu">
                    <svg x-show="!open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    <svg x-show="open" style="display:none" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile dropdown --}}
        <div x-show="open" style="display:none" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden border-t border-white/5 bg-[#0f172a]/95 backdrop-blur-lg">
            <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col gap-1">
                <a href="#features" @click="open = false" class="text-slate-300 hover:text-white hover:bg-white/5 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">@lang('nav.features')</a>
                <a href="#pricing" @click="open = false" class="text-slate-300 hover:text-white hover:bg-white/5 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">@lang('nav.pricing')</a>
                <a href="#faq" @click="open = false" class="text-slate-300 hover:text-white hover:bg-white/5 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">@lang('nav.faq')</a>
                <div class="border-t border-white/5 my-1"></div>
                @auth
                    <a href="{{ route('app.insights') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors text-center">Dashboard</a>
                @else
                    <a href="{{ route('register.org') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors text-center">Register Organization</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors text-center">@lang('nav.register')</a>
                    @endif
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white hover:bg-white/5 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">@lang('nav.login')</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="sm:min-h-screen flex items-center justify-center pt-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-teal-500/5 via-transparent to-transparent pointer-events-none"></div>
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-teal-500/3 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center py-12 sm:py-20 relative">
            <span class="inline-block text-teal-400 font-semibold text-xs sm:text-sm tracking-widest uppercase mb-6 bg-teal-500/10 border border-teal-500/20 rounded-full px-4 py-2">
                @lang('hero.badge')
            </span>

            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold text-white leading-tight tracking-tight">
                @lang('hero.heading_1')<br>
                <span class="text-teal-300">@lang('hero.heading_2')</span>
            </h1>

            <p class="mt-6 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
                @lang('hero.subtitle')
            </p>

            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                    <a href="{{ route('app.insights') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('register.org') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        Register Organization
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-8 py-3.5 rounded-full font-semibold text-base border border-slate-700 transition-colors">
                            @lang('hero.cta')
                        </a>
                    @endif
                    <a href="#features" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-8 py-3.5 rounded-full font-semibold text-base border border-slate-700 transition-colors">
                        @lang('hero.how_it_works')
                    </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-t border-white/5 bg-[#0f172a]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 sm:gap-12 text-center">
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-teal-300">@lang('stat.digital_donors_value')</div>
                    <div class="mt-2 text-sm text-slate-500">@lang('stat.digital_donors_label')</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-teal-300">@lang('stat.target_raised_value')</div>
                    <div class="mt-2 text-sm text-slate-500">@lang('stat.target_raised_label')</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-teal-300">@lang('stat.retention_value')</div>
                    <div class="mt-2 text-sm text-slate-500">@lang('stat.retention_label')</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Product Preview --}}
    <section class="py-20 sm:py-28 overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-white">@lang('preview.title')</h2>
                <p class="mt-3 text-slate-400 max-w-xl mx-auto">@lang('preview.subtitle')</p>
            </div>

            {{-- Main dashboard mockup --}}
            <div class="rounded-2xl overflow-hidden border border-white/10 shadow-[0_0_80px_-20px_rgba(20,184,166,0.25)]">
                {{-- Browser chrome --}}
                <div class="bg-slate-900 px-4 py-3 flex items-center gap-3 border-b border-white/5">
                    <div class="flex gap-1.5 shrink-0">
                        <div class="w-3 h-3 rounded-full bg-red-500/50"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500/50"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500/50"></div>
                    </div>
                    <div class="flex-1 bg-slate-800 rounded-md text-xs text-slate-500 px-3 py-1.5 max-w-xs mx-auto text-center truncate">
                        app.getihsan.my/app/insights
                    </div>
                </div>

                {{-- App layout --}}
                <div class="flex bg-[#111827]">
                    {{-- Sidebar --}}
                    <div class="hidden sm:flex w-52 shrink-0 bg-[#0f172a] border-r border-white/5 flex-col p-3">
                        <div class="px-3 py-3 mb-2">
                            <span class="text-white font-bold text-lg">Ihsan</span>
                        </div>
                        <nav class="flex flex-col gap-0.5 text-sm">
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-teal-600/20 text-teal-400 font-medium">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                                Insights
                            </div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                                Donations
                            </div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Recurring
                            </div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                                Campaigns
                            </div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                Donors
                            </div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.401.604-.401.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z"/></svg>
                                Elements
                            </div>
                        </nav>
                    </div>

                    {{-- Main content --}}
                    <div class="flex-1 min-w-0 p-4 sm:p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-white font-semibold text-lg">Insights</h3>
                            <div class="flex gap-2">
                                <div class="bg-slate-800 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-slate-400">Last 30 days</div>
                                <div class="hidden sm:block bg-slate-800 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-slate-400">All Campaigns</div>
                            </div>
                        </div>

                        {{-- Metric cards --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                            <div class="bg-slate-800/50 border border-white/5 rounded-xl p-3 sm:p-4">
                                <div class="text-xs text-slate-500 mb-1">Total Raised</div>
                                <div class="text-base sm:text-xl font-bold text-white">RM 12,480</div>
                                <div class="text-xs text-teal-400 mt-1">↑ 18%</div>
                            </div>
                            <div class="bg-slate-800/50 border border-white/5 rounded-xl p-3 sm:p-4">
                                <div class="text-xs text-slate-500 mb-1">MRR</div>
                                <div class="text-base sm:text-xl font-bold text-white">RM 3,240</div>
                                <div class="text-xs text-teal-400 mt-1">↑ 12%</div>
                            </div>
                            <div class="bg-slate-800/50 border border-white/5 rounded-xl p-3 sm:p-4">
                                <div class="text-xs text-slate-500 mb-1">Active Donors</div>
                                <div class="text-base sm:text-xl font-bold text-white">156</div>
                                <div class="text-xs text-teal-400 mt-1">↑ 8 new</div>
                            </div>
                            <div class="bg-slate-800/50 border border-white/5 rounded-xl p-3 sm:p-4">
                                <div class="text-xs text-slate-500 mb-1">Recurring</div>
                                <div class="text-base sm:text-xl font-bold text-white">87</div>
                                <div class="text-xs text-slate-500 mt-1">56%</div>
                            </div>
                        </div>

                        {{-- Chart --}}
                        <div class="bg-slate-800/30 border border-white/5 rounded-xl p-4">
                            <div class="text-xs text-slate-500 mb-3">Donation Revenue</div>
                            <svg class="w-full" height="80" viewBox="0 0 500 80" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="rgb(20,184,166)" stop-opacity="0.25"/>
                                        <stop offset="100%" stop-color="rgb(20,184,166)" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                <path d="M0,72 C50,68 80,55 120,48 C160,42 185,58 230,38 C275,18 300,28 345,18 C380,10 440,14 500,5 L500,80 L0,80 Z" fill="url(#chartGrad)"/>
                                <path d="M0,72 C50,68 80,55 120,48 C160,42 185,58 230,38 C275,18 300,28 345,18 C380,10 440,14 500,5" fill="none" stroke="rgb(20,184,166)" stroke-width="2"/>
                            </svg>
                            <div class="flex justify-between text-xs text-slate-600 mt-2">
                                <span>1 May</span><span>8 May</span><span>15 May</span><span>22 May</span><span>30 May</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Two smaller mockups --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
                {{-- Campaigns mockup --}}
                <div class="rounded-2xl overflow-hidden border border-white/10 shadow-xl">
                    <div class="bg-slate-900 px-4 py-2.5 flex items-center gap-2 border-b border-white/5">
                        <div class="flex gap-1 shrink-0">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500/50"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/50"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500/50"></div>
                        </div>
                        <span class="text-xs text-slate-600 ml-2 truncate">app.getihsan.my/app/campaigns</span>
                    </div>
                    <div class="bg-[#111827] p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-white">Campaigns</span>
                            <span class="text-xs bg-teal-600 text-white px-3 py-1 rounded-full font-medium">+ New</span>
                        </div>
                        <div class="space-y-2">
                            <div class="bg-slate-800/50 border border-white/5 rounded-xl p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-white">Tabung Masjid Al-Falah</span>
                                    <span class="text-xs bg-teal-500/15 text-teal-400 px-2 py-0.5 rounded-full">Active</span>
                                </div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="flex-1 bg-slate-700 rounded-full h-1.5">
                                        <div class="bg-teal-500 h-1.5 rounded-full" style="width:68%"></div>
                                    </div>
                                    <span class="text-xs text-slate-400 shrink-0">68%</span>
                                </div>
                                <span class="text-xs text-slate-500">RM 6,800 / RM 10,000</span>
                            </div>
                            <div class="bg-slate-800/50 border border-white/5 rounded-xl p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-white">Bantuan Asnaf Ramadan</span>
                                    <span class="text-xs bg-teal-500/15 text-teal-400 px-2 py-0.5 rounded-full">Active</span>
                                </div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="flex-1 bg-slate-700 rounded-full h-1.5">
                                        <div class="bg-teal-500 h-1.5 rounded-full" style="width:45%"></div>
                                    </div>
                                    <span class="text-xs text-slate-400 shrink-0">45%</span>
                                </div>
                                <span class="text-xs text-slate-500">RM 4,500 / RM 10,000</span>
                            </div>
                            <div class="bg-slate-800/50 border border-white/5 rounded-xl p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-white">Tabung Pendidikan Anak Yatim</span>
                                    <span class="text-xs bg-slate-700 text-slate-400 px-2 py-0.5 rounded-full">Draft</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Donor Portal mockup --}}
                <div class="rounded-2xl overflow-hidden border border-white/10 shadow-xl">
                    <div class="bg-slate-900 px-4 py-2.5 flex items-center gap-2 border-b border-white/5">
                        <div class="flex gap-1 shrink-0">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500/50"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/50"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500/50"></div>
                        </div>
                        <span class="text-xs text-slate-600 ml-2 truncate">getihsan.my/my/donations</span>
                    </div>
                    <div class="bg-[#111827] p-4">
                        <div class="mb-3">
                            <span class="text-sm font-semibold text-white">My Donations</span>
                            <p class="text-xs text-slate-500 mt-0.5">Hafizah bt. Azlan</p>
                        </div>
                        <div class="bg-teal-600/10 border border-teal-500/20 rounded-xl p-3 mb-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs text-teal-400 font-medium">Tabung Masjid Al-Falah</div>
                                    <div class="text-xs text-slate-400 mt-0.5">RM 50 / month · Active</div>
                                </div>
                                <div class="flex gap-1">
                                    <span class="text-xs bg-slate-700 text-slate-300 px-2 py-1 rounded-md">Pause</span>
                                    <span class="text-xs bg-slate-700 text-slate-300 px-2 py-1 rounded-md">Edit</span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between bg-slate-800/40 rounded-lg p-2.5">
                                <span class="text-xs text-slate-400">1 Jun 2026 · One-time</span>
                                <span class="text-xs text-white font-medium">RM 100</span>
                            </div>
                            <div class="flex items-center justify-between bg-slate-800/40 rounded-lg p-2.5">
                                <span class="text-xs text-slate-400">1 May 2026 · Recurring</span>
                                <span class="text-xs text-white font-medium">RM 50</span>
                            </div>
                            <div class="flex items-center justify-between bg-slate-800/40 rounded-lg p-2.5">
                                <span class="text-xs text-slate-400">1 Apr 2026 · Recurring</span>
                                <span class="text-xs text-white font-medium">RM 50</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="bg-[#131d31] py-20 sm:py-28">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-white text-center">@lang('features.title')</h2>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 hover:border-teal-500/30 transition-colors group">
                    <div class="w-10 h-10 bg-teal-600/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-600/30 transition-colors">
                        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold text-lg">@lang('features.element.title')</h3>
                    <p class="mt-2 text-slate-400 text-sm leading-relaxed">@lang('features.element.desc')</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 hover:border-teal-500/30 transition-colors group">
                    <div class="w-10 h-10 bg-teal-600/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-600/30 transition-colors">
                        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold text-lg">@lang('features.recurring.title')</h3>
                    <p class="mt-2 text-slate-400 text-sm leading-relaxed">@lang('features.recurring.desc')</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 hover:border-teal-500/30 transition-colors group">
                    <div class="w-10 h-10 bg-teal-600/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-600/30 transition-colors">
                        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold text-lg">@lang('features.dashboard.title')</h3>
                    <p class="mt-2 text-slate-400 text-sm leading-relaxed">@lang('features.dashboard.desc')</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 hover:border-teal-500/30 transition-colors group">
                    <div class="w-10 h-10 bg-teal-600/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-600/30 transition-colors">
                        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold text-lg">@lang('features.stripe.title')</h3>
                    <p class="mt-2 text-slate-400 text-sm leading-relaxed">@lang('features.stripe.desc')</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 hover:border-teal-500/30 transition-colors group">
                    <div class="w-10 h-10 bg-teal-600/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-600/30 transition-colors">
                        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <h3 class="text-white font-semibold text-lg">@lang('features.campaign.title')</h3>
                    <p class="mt-2 text-slate-400 text-sm leading-relaxed">@lang('features.campaign.desc')</p>
                </div>

                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 hover:border-teal-500/30 transition-colors group">
                    <div class="w-10 h-10 bg-teal-600/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-600/30 transition-colors">
                        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.981l7.5-4.039a2.25 2.25 0 012.134 0l7.5 4.039a2.25 2.25 0 011.183 1.98V19.5z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold text-lg">@lang('features.receipt.title')</h3>
                    <p class="mt-2 text-slate-400 text-sm leading-relaxed">@lang('features.receipt.desc')</p>
                </div>
            </div>
        </div>
    </section>

    {{-- For NGOs vs Donors --}}
    <section class="py-20 sm:py-28">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-white text-center mb-12">@lang('for.title')</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gradient-to-b from-slate-800/50 to-slate-900/50 border border-slate-700/50 rounded-2xl p-8 hover:border-teal-500/20 transition-colors">
                    <span class="text-xs font-semibold text-teal-400 tracking-widest uppercase">@lang('for.ngo.label')</span>
                    <h3 class="text-xl font-bold text-white mt-3">@lang('for.ngo.title')</h3>
                    <ul class="mt-6 space-y-3">
                        @foreach (['for.ngo.point_1','for.ngo.point_2','for.ngo.point_3','for.ngo.point_4','for.ngo.point_5','for.ngo.point_6','for.ngo.point_7'] as $key)
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang($key)</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-gradient-to-b from-slate-800/50 to-slate-900/50 border border-slate-700/50 rounded-2xl p-8 hover:border-teal-500/20 transition-colors">
                    <span class="text-xs font-semibold text-teal-400 tracking-widest uppercase">@lang('for.donor.label')</span>
                    <h3 class="text-xl font-bold text-white mt-3">@lang('for.donor.title')</h3>
                    <ul class="mt-6 space-y-3">
                        @foreach (['for.donor.point_1','for.donor.point_2','for.donor.point_3','for.donor.point_4','for.donor.point_5','for.donor.point_6'] as $key)
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang($key)</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="bg-[#131d31] py-20 sm:py-28">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-white">@lang('pricing.title')</h2>
            <p class="mt-3 text-slate-400">@lang('pricing.subtitle')</p>

            <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-lg mx-auto">
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 text-left">
                    <div class="text-white font-semibold text-lg">@lang('pricing.mvp.name')</div>
                    <div class="mt-2 text-4xl font-extrabold text-teal-300">@lang('pricing.mvp.price')</div>
                    <div class="mt-1 text-sm text-slate-500">@lang('pricing.mvp.fee')</div>
                </div>

                <div class="bg-slate-800/50 border border-teal-500/30 rounded-2xl p-8 text-left">
                    <div class="text-white font-semibold text-lg">@lang('pricing.coming.name')</div>
                    <div class="mt-4 text-sm text-slate-400">@lang('pricing.coming.desc')</div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="py-20 sm:py-28">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-white text-center">@lang('faq.title')</h2>

            <div class="mt-10 space-y-3">
                <details class="group bg-slate-800/30 border border-slate-700/50 rounded-xl overflow-hidden">
                    <summary class="flex items-center justify-between p-5 cursor-pointer text-white font-medium hover:bg-slate-700/30 transition-colors list-none">
                        @lang('faq.q1.q')
                        <svg class="w-5 h-5 text-slate-500 group-open:rotate-180 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-slate-400 leading-relaxed">
                        @lang('faq.q1.a')
                    </div>
                </details>

                <details class="group bg-slate-800/30 border border-slate-700/50 rounded-xl overflow-hidden">
                    <summary class="flex items-center justify-between p-5 cursor-pointer text-white font-medium hover:bg-slate-700/30 transition-colors list-none">
                        @lang('faq.q2.q')
                        <svg class="w-5 h-5 text-slate-500 group-open:rotate-180 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-slate-400 leading-relaxed">
                        @lang('faq.q2.a')
                    </div>
                </details>

                <details class="group bg-slate-800/30 border border-slate-700/50 rounded-xl overflow-hidden">
                    <summary class="flex items-center justify-between p-5 cursor-pointer text-white font-medium hover:bg-slate-700/30 transition-colors list-none">
                        @lang('faq.q3.q')
                        <svg class="w-5 h-5 text-slate-500 group-open:rotate-180 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-slate-400 leading-relaxed">
                        @lang('faq.q3.a')
                    </div>
                </details>

                <details class="group bg-slate-800/30 border border-slate-700/50 rounded-xl overflow-hidden">
                    <summary class="flex items-center justify-between p-5 cursor-pointer text-white font-medium hover:bg-slate-700/30 transition-colors list-none">
                        @lang('faq.q4.q')
                        <svg class="w-5 h-5 text-slate-500 group-open:rotate-180 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-slate-400 leading-relaxed">
                        @lang('faq.q4.a')
                    </div>
                </details>

                <details class="group bg-slate-800/30 border border-slate-700/50 rounded-xl overflow-hidden">
                    <summary class="flex items-center justify-between p-5 cursor-pointer text-white font-medium hover:bg-slate-700/30 transition-colors list-none">
                        @lang('faq.q5.q')
                        <svg class="w-5 h-5 text-slate-500 group-open:rotate-180 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-slate-400 leading-relaxed">
                        @lang('faq.q5.a')
                    </div>
                </details>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 sm:py-28 text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-t from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <h2 class="text-2xl sm:text-3xl font-bold text-white">@lang('cta.title')</h2>
            <p class="mt-3 text-slate-400">@lang('cta.subtitle')</p>

            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                    <a href="{{ route('app.insights') }}" class="inline-block bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('register.org') }}" class="inline-block bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        Register Organization
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-block bg-slate-800 hover:bg-slate-700 text-slate-200 px-8 py-3.5 rounded-full font-semibold text-base border border-slate-700 transition-colors">
                            @lang('cta.button')
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-white/5 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-600">
            <span>@lang('footer.copyright')</span>
            <div class="flex items-center gap-6">
                <a href="mailto:@lang('footer.email')" class="hover:text-slate-400 transition-colors">@lang('footer.email')</a>
                <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-xs font-medium text-slate-600 hover:text-slate-400 transition-colors border border-white/10 rounded-full px-3 py-1">
                    @lang('nav.switch_language')
                </a>
            </div>
        </div>
    </footer>
</x-layouts::landing>
