<x-layouts::landing>
    <x-slot:title>@lang('site.title')</x-slot:title>

    {{-- Nav --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-[#0f172a]/80 backdrop-blur-lg border-b border-white/5">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-white font-bold text-lg tracking-tight">Ihsan</a>

            <div class="hidden md:flex items-center gap-6 text-sm">
                <a href="#features" class="text-slate-400 hover:text-white transition-colors">@lang('nav.features')</a>
                <a href="#pricing" class="text-slate-400 hover:text-white transition-colors">@lang('nav.pricing')</a>
                <a href="#faq" class="text-slate-400 hover:text-white transition-colors">@lang('nav.faq')</a>

                @auth
                    <a href="{{ url('/app') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-5 py-2 rounded-full font-semibold text-sm transition-colors">Dashboard</a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-5 py-2 rounded-full font-semibold text-sm transition-colors">@lang('nav.register')</a>
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
                @auth
                    <a href="{{ url('/app') }}" class="bg-teal-600 text-white px-4 py-2 rounded-full text-sm font-semibold">Dashboard</a>
                @elseif (Route::has('register'))
                    <a href="{{ route('register') }}" class="bg-teal-600 text-white px-4 py-2 rounded-full text-sm font-semibold">@lang('nav.register')</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="min-h-screen flex items-center justify-center pt-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-teal-500/5 via-transparent to-transparent pointer-events-none"></div>
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-teal-500/3 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center py-20 relative">
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
                    <a href="{{ url('/app') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        Dashboard
                    </a>
                @elseif (Route::has('register'))
                    <a href="{{ route('register') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        @lang('hero.cta')
                    </a>
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
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang('for.ngo.point_1')</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang('for.ngo.point_2')</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang('for.ngo.point_3')</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-b from-slate-800/50 to-slate-900/50 border border-slate-700/50 rounded-2xl p-8 hover:border-teal-500/20 transition-colors">
                    <span class="text-xs font-semibold text-teal-400 tracking-widest uppercase">@lang('for.donor.label')</span>
                    <h3 class="text-xl font-bold text-white mt-3">@lang('for.donor.title')</h3>
                    <ul class="mt-6 space-y-3">
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang('for.donor.point_1')</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang('for.donor.point_2')</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-400">
                            <svg class="w-5 h-5 text-teal-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>@lang('for.donor.point_3')</span>
                        </li>
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

            <div class="mt-8">
                @auth
                    <a href="{{ url('/app') }}" class="inline-block bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        Dashboard
                    </a>
                @elseif (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-block bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        @lang('cta.button')
                    </a>
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
