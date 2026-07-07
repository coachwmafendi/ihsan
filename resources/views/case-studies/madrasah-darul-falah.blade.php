<x-layouts::landing>
    <x-slot:title>@lang('case.meta_title')</x-slot:title>
    <x-slot:bodyClass>bg-white text-slate-600</x-slot:bodyClass>

    {{-- Nav --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/85 backdrop-blur-lg border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-slate-900 font-bold text-lg tracking-tight">
                <x-app-logo-icon class="h-7 w-auto" />
                <span>Ihsan</span>
            </a>

            <div class="flex items-center gap-4 sm:gap-6 text-sm">
                <a href="{{ route('home') }}#features" class="hidden sm:inline text-slate-500 hover:text-slate-900 transition-colors">@lang('nav.features')</a>
                <a href="{{ route('home') }}#pricing" class="hidden sm:inline text-slate-500 hover:text-slate-900 transition-colors">@lang('nav.pricing')</a>

                @auth
                    <a href="{{ route('app.dashboard') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-5 py-2 rounded-full font-semibold text-sm transition-colors">Dashboard</a>
                @else
                    <a href="{{ route('register.org') }}" class="bg-teal-600 hover:bg-teal-500 text-white px-5 py-2 rounded-full font-semibold text-sm transition-colors">@lang('nav.register_organization')</a>
                @endauth

                <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors border border-slate-200 rounded-full px-3 py-1.5">
                    @lang('nav.switch_language')
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-32 pb-10 sm:pt-40 sm:pb-14 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-teal-50 via-white to-white pointer-events-none"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
            <span class="inline-block text-teal-700 font-semibold text-xs sm:text-sm tracking-widest uppercase mb-6 bg-teal-50 border border-teal-200 rounded-full px-4 py-2">
                @lang('case.label')
            </span>

            <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight tracking-tight">
                @lang('case.title')
            </h1>

            <p class="mt-6 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                @lang('case.subtitle')
            </p>
        </div>
    </section>

    {{-- Hero image --}}
    <section class="pb-12 sm:pb-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <figure>
                <img src="{{ asset('images/case-studies/madrasah-darul-falah/hero.svg') }}"
                     alt="@lang('case.img.hero_caption')"
                     width="1600" height="720"
                     class="w-full h-auto rounded-3xl border border-slate-200 shadow-lg shadow-slate-200/60 object-cover">
                <figcaption class="mt-3 text-center text-xs sm:text-sm text-slate-400">@lang('case.img.hero_caption')</figcaption>
            </figure>
        </div>
    </section>

    {{-- Key results --}}
    <section class="pb-16 sm:pb-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach (['stat1', 'stat2', 'stat3', 'stat4'] as $stat)
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center shadow-sm">
                        <div class="text-3xl sm:text-4xl font-extrabold text-teal-600">@lang("case.{$stat}.value")</div>
                        <div class="mt-2 text-xs sm:text-sm text-slate-500 leading-snug">@lang("case.{$stat}.label")</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Story --}}
    <section class="pb-20 sm:pb-28">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-[1fr_300px] gap-12 lg:gap-16">
            <article class="space-y-12 min-w-0">
                {{-- About --}}
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">@lang('case.about.title')</h2>
                    <p class="mt-4 text-slate-600 leading-relaxed">@lang('case.about.p1')</p>
                    <p class="mt-4 text-slate-600 leading-relaxed">@lang('case.about.p2')</p>

                    <figure class="mt-8">
                        <img src="{{ asset('images/case-studies/madrasah-darul-falah/students.svg') }}"
                             alt="@lang('case.img.students_caption')"
                             width="1000" height="560"
                             class="w-full h-auto rounded-2xl border border-slate-200 object-cover"
                             loading="lazy">
                        <figcaption class="mt-3 text-xs sm:text-sm text-slate-400">@lang('case.img.students_caption')</figcaption>
                    </figure>
                </div>

                {{-- Challenge --}}
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">@lang('case.challenge.title')</h2>
                    <p class="mt-4 text-slate-600 leading-relaxed">@lang('case.challenge.p1')</p>
                    <p class="mt-4 text-slate-600 leading-relaxed">@lang('case.challenge.p2')</p>

                    <blockquote class="mt-8 border-l-2 border-teal-500 pl-6">
                        <p class="text-lg sm:text-xl text-slate-800 font-medium leading-relaxed">&ldquo;@lang('case.challenge.quote')&rdquo;</p>
                        <footer class="mt-3 text-sm text-slate-400">— @lang('case.challenge.quote_by')</footer>
                    </blockquote>
                </div>

                {{-- Solution --}}
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">@lang('case.solution.title')</h2>
                    <p class="mt-4 text-slate-600 leading-relaxed">@lang('case.solution.intro')</p>

                    <ul class="mt-6 space-y-3">
                        @foreach (['point1', 'point2', 'point3', 'point4', 'point5', 'point6'] as $point)
                            <li class="flex items-start gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-teal-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm sm:text-base leading-relaxed">@lang("case.solution.{$point}")</span>
                            </li>
                        @endforeach
                    </ul>

                    <figure class="mt-8">
                        <img src="{{ asset('images/case-studies/madrasah-darul-falah/donation-form.svg') }}"
                             alt="@lang('case.img.form_caption')"
                             width="1000" height="560"
                             class="w-full h-auto rounded-2xl border border-slate-200 object-cover"
                             loading="lazy">
                        <figcaption class="mt-3 text-xs sm:text-sm text-slate-400">@lang('case.img.form_caption')</figcaption>
                    </figure>
                </div>

                {{-- Results --}}
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">@lang('case.results.title')</h2>
                    <p class="mt-4 text-slate-600 leading-relaxed">@lang('case.results.p1')</p>
                    <p class="mt-4 text-slate-600 leading-relaxed">@lang('case.results.p2')</p>

                    <blockquote class="mt-8 bg-teal-50 border border-teal-100 rounded-2xl p-6 sm:p-8">
                        <svg class="w-8 h-8 text-teal-300" fill="currentColor" viewBox="0 0 24 24"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
                        <p class="mt-4 text-lg sm:text-xl text-slate-800 font-medium leading-relaxed">@lang('case.results.quote')</p>
                        <footer class="mt-4 text-sm text-slate-500">— @lang('case.results.quote_by')</footer>
                    </blockquote>

                    <figure class="mt-8">
                        <img src="{{ asset('images/case-studies/madrasah-darul-falah/new-building.svg') }}"
                             alt="@lang('case.img.building_caption')"
                             width="1000" height="560"
                             class="w-full h-auto rounded-2xl border border-slate-200 object-cover"
                             loading="lazy">
                        <figcaption class="mt-3 text-xs sm:text-sm text-slate-400">@lang('case.img.building_caption')</figcaption>
                    </figure>
                </div>
            </article>

            {{-- At a glance --}}
            <aside class="order-first lg:order-none">
                <div class="lg:sticky lg:top-24 bg-slate-50 border border-slate-200 rounded-2xl p-6">
                    <h2 class="text-sm font-semibold text-teal-700 tracking-widest uppercase">@lang('case.facts.title')</h2>

                    <dl class="mt-5 space-y-4 text-sm">
                        @foreach (['org', 'location', 'sector', 'founded', 'size', 'since'] as $fact)
                            <div>
                                <dt class="text-slate-400">@lang("case.facts.{$fact}_label")</dt>
                                <dd class="mt-0.5 text-slate-800 font-medium">@lang("case.facts.{$fact}")</dd>
                            </div>
                        @endforeach
                        <div>
                            <dt class="text-slate-400">@lang('case.facts.website_label')</dt>
                            <dd class="mt-0.5">
                                <a href="https://madrasahdarulfalah.org/" target="_blank" rel="noopener" class="text-teal-600 hover:text-teal-500 font-medium transition-colors">madrasahdarulfalah.org</a>
                            </dd>
                        </div>
                    </dl>

                    <a href="https://madrasahdarulfalah.org/" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 border border-slate-300 hover:border-slate-400 rounded-full px-4 py-2 transition-colors">
                        @lang('case.facts.visit_website')
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    </a>
                </div>
            </aside>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 sm:py-28 text-center relative overflow-hidden bg-gradient-to-b from-white to-teal-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">@lang('case.cta.title')</h2>
            <p class="mt-3 text-slate-600">@lang('case.cta.subtitle')</p>

            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                    <a href="{{ route('app.dashboard') }}" class="inline-block bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('register.org') }}" class="inline-block bg-teal-600 hover:bg-teal-500 text-white px-8 py-3.5 rounded-full font-semibold text-base transition-all hover:shadow-lg hover:shadow-teal-500/25">
                        @lang('nav.register_organization')
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-block bg-white hover:bg-slate-50 text-slate-700 px-8 py-3.5 rounded-full font-semibold text-base border border-slate-300 transition-colors">
                            @lang('cta.button')
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 py-8 bg-teal-50/40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-400">
            <span>@lang('footer.copyright')</span>
            <div class="flex items-center gap-6">
                <a href="mailto:@lang('footer.email')" class="hover:text-slate-600 transition-colors">@lang('footer.email')</a>
                <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-xs font-medium text-slate-400 hover:text-slate-600 transition-colors border border-slate-200 rounded-full px-3 py-1">
                    @lang('nav.switch_language')
                </a>
            </div>
        </div>
    </footer>
</x-layouts::landing>
