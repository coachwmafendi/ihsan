@php
    $loaderSnippet = '<script src="' . url('/e/loader.js') . '" data-ihsan-loader async></script>';
@endphp

<div class="space-y-8">

    {{-- Page Header --}}
    <x-ui.page-header title="Installation" subtitle="Three quick steps to embed your donation elements on any website." />



    {{-- ─── Step 1: Choose how to embed ────────────────────────────────────── --}}
    <div>
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">1</span>
            <div>
                <h2 class="text-base font-semibold text-slate-900">Choose how to add it to your website</h2>
                <p class="mt-0.5 text-sm text-slate-500">Pick the easiest option for you. Donation tracking works the same either way.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            {{-- Option A: ready-made embed code --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center gap-2">
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Recommended</span>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Copy-paste ready code</h3>
                <p class="mt-1 text-sm text-slate-500">Go to Elements, copy the ready-made code for each donation button, form, or popup, and paste it where you want it on your website. No extra script needed.</p>
                <ul class="mt-3 space-y-1 text-xs text-slate-500">
                    <li class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Easiest option</li>
                    <li class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Works right away</li>
                </ul>
                <div class="mt-4">
                    <x-ui.button variant="outline" size="sm" href="{{ route('app.elements.index') }}">
                        Go to Elements
                    </x-ui.button>
                </div>
            </div>

            {{-- Option B: one script for custom buttons --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-slate-900">One script for custom buttons</h3>
                <p class="mt-1 text-sm text-slate-500">Add a single script to your website once, then connect your own buttons using the token from each element. Best if you want full control over the button design.</p>
                <ul class="mt-3 space-y-1 text-xs text-slate-500">
                    <li class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Use your own button designs</li>
                    <li class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Developer / advanced option</li>
                </ul>
                <div class="mt-4" x-data="{ copied: false }">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500">Script to paste in your website header</span>
                            <button
                                type="button"
                                x-on:click="navigator.clipboard.writeText(@js($loaderSnippet)); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center gap-1 text-xs font-medium text-slate-700 hover:text-slate-900"
                            >
                                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                            </button>
                        </div>
                        <pre class="mt-2 overflow-x-auto rounded bg-slate-950 px-3 py-2 text-[10px] leading-relaxed text-slate-300"><code>{{ $loaderSnippet }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Step 2: Tracking IDs ───────────────────────────────────────────── --}}
    <div>
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">2</span>
            <div>
                <h2 class="text-base font-semibold text-slate-900">Connect donation tracking</h2>
                <p class="mt-0.5 text-sm text-slate-500">Add your Meta Pixel, Google Analytics, Google Ads, TikTok, etc. so you can see donation events in your ad and analytics accounts.</p>
            </div>
        </div>

        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50">
                    <svg class="h-4.5 w-4.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 013 5.25m18 0H3"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Meta, Google Analytics, Google Ads, TikTok</p>
                    <p class="text-xs text-slate-500">Configure once in Tracking & Analytics — applies to all your elements.</p>
                </div>
            </div>
            <x-ui.button variant="outline" size="sm" href="{{ route('app.settings.tracking') }}">
                Configure tracking
            </x-ui.button>
        </div>
    </div>

    {{-- ─── Step 3: Element embed codes ────────────────────────────────────── --}}
    <div>
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">3</span>
            <div>
                <h2 class="text-base font-semibold text-slate-900">Manage your elements</h2>
                <p class="mt-0.5 text-sm text-slate-500">View, edit, or copy the embed code for each active element.</p>
            </div>
        </div>

        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50">
                    <svg class="h-4.5 w-4.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zm-7.518-.267A8.25 8.25 0 1120.25 10.5M8.288 14.212A5.25 5.25 0 1117.25 10.5"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">{{ count($this->elements) }} active element{{ count($this->elements) === 1 ? '' : 's' }}</p>
                    <p class="text-xs text-slate-500">Each row has its own copy button for the embed snippet or token.</p>
                </div>
            </div>
            <x-ui.button variant="outline" size="sm" href="{{ route('app.elements.index') }}">
                Go to Elements
            </x-ui.button>
        </div>
    </div>

    {{-- ─── Platform Guides ────────────────────────────────────────────────── --}}
    <div>
        <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-900">Platform guides</h2>
            <p class="mt-0.5 text-sm text-slate-500">Step-by-step for pasting snippets or adding the loader on your website platform.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white divide-y divide-slate-100" x-data="{ open: null }">

            @php
                $guides = [
                    'html' => [
                        'label' => 'Custom HTML / Any website',
                        'icon_bg' => 'bg-orange-50',
                        'icon_color' => 'text-orange-500',
                        'steps' => [
                            'Simplest: Go to <strong>Elements</strong>, copy the snippet for each element, and paste it where it should appear.',
                            'Advanced option: Paste the loader snippet inside the <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">&lt;head&gt;</code> section once, then add <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">data-ihsan="TOKEN"</code> to your own buttons.',
                        ],
                    ],
                    'wp' => [
                        'label' => 'WordPress',
                        'icon_bg' => 'bg-blue-50',
                        'icon_color' => 'text-blue-600',
                        'steps' => [
                            'Simplest: Add an HTML block on the page and paste the per-element snippet from the Elements page.',
                            'Advanced option: Install <strong>Insert Headers and Footers</strong>, paste the loader snippet in the <em>Header</em> section, then add <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">data-ihsan="TOKEN"</code> to your button HTML.',
                        ],
                    ],
                    'webflow' => [
                        'label' => 'Webflow',
                        'icon_bg' => 'bg-indigo-50',
                        'icon_color' => 'text-indigo-600',
                        'steps' => [
                            'Simplest: Add an Embed element and paste the per-element snippet from the Elements page.',
                            'Advanced option: Paste the loader snippet in <strong>Project Settings → Custom Code → Head Code</strong>, then add a custom attribute <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">data-ihsan</code> with value <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">TOKEN</code> to your button.',
                            'Publish your site.',
                        ],
                    ],
                    'squarespace' => [
                        'label' => 'Squarespace',
                        'icon_bg' => 'bg-slate-100',
                        'icon_color' => 'text-slate-700',
                        'steps' => [
                            'Simplest: On your page, add a <strong>Code Block</strong> and paste the per-element snippet from the Elements page.',
                            'Advanced option: Go to <strong>Website → Pages → Website Tools → Code Injection</strong> and paste the loader snippet in the <em>Header</em> section, then use Code Blocks with <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">data-ihsan="TOKEN"</code>.',
                        ],
                    ],
                    'wix' => [
                        'label' => 'Wix',
                        'icon_bg' => 'bg-amber-50',
                        'icon_color' => 'text-amber-600',
                        'steps' => [
                            'Simplest: Add an HTML iframe / embed element and paste the per-element snippet from the Elements page.',
                            'Advanced option: Go to <strong>Settings → Advanced → Custom Code</strong>, add the loader snippet to the <em>Head</em> on all pages, then trigger <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">Ihsan.open(\'TOKEN\')</code> from your button.',
                        ],
                    ],
                ];
            @endphp

            @foreach ($guides as $key => $guide)
                <div>
                    <button type="button" x-on:click="open = open === '{{ $key }}' ? null : '{{ $key }}'" class="flex w-full items-center justify-between px-5 py-4 text-left hover:bg-slate-50/60 transition">
                        <div class="flex items-center gap-3">
                            <div class="flex h-7 w-7 items-center justify-center rounded-md {{ $guide['icon_bg'] }}">
                                <svg class="h-4 w-4 {{ $guide['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-slate-900">{{ $guide['label'] }}</span>
                        </div>
                        <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open === '{{ $key }}' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === '{{ $key }}'" x-collapse class="border-t border-slate-100">
                        <ol class="space-y-3 px-5 py-4">
                            @foreach ($guide['steps'] as $i => $step)
                                <li class="flex gap-3 text-sm text-slate-600">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-700 mt-0.5">{{ $i + 1 }}</span>
                                    <span>{!! $step !!}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Help footer --}}
    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-800">Need help?</p>
                <p class="mt-0.5 text-xs text-slate-500">Forward this page to your web developer — the snippets above contain everything they need. No Ihsan account access required.</p>
            </div>
        </div>
    </div>

</div>
