@php
    $loaderSnippet = '<script src="' . url('/e/loader.js') . '" data-ihsan-loader async></script>';
@endphp

<div class="space-y-8">

    {{-- Page Header --}}
    <x-ui.page-header title="Installation" subtitle="Three quick steps to embed your donation elements on any website." />

    {{-- ─── Step 1: Loader snippet ─────────────────────────────────────────── --}}
    <div>
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">1</span>
            <div>
                <h2 class="text-base font-semibold text-slate-900">Add the loader to your website — once</h2>
                <p class="mt-0.5 text-sm text-slate-500">Paste this snippet inside the <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">&lt;head&gt;</code> section of every page. Done once, ever.</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white" x-data="{ copied: false }">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span class="text-xs font-medium text-slate-500">HTML · paste once in <code class="font-mono">&lt;head&gt;</code></span>
                </div>
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText(@js($loaderSnippet)); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <template x-if="!copied">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/>
                        </svg>
                    </template>
                    <template x-if="copied">
                        <svg class="h-3.5 w-3.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </template>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
            <div class="px-5 py-4">
                <pre class="overflow-x-auto rounded-lg bg-slate-950 px-4 py-3 text-xs leading-relaxed text-slate-300"><code>{{ $loaderSnippet }}</code></pre>
            </div>
        </div>
    </div>

    {{-- ─── Step 2: Tracking IDs ───────────────────────────────────────────── --}}
    <div>
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">2</span>
            <div>
                <h2 class="text-base font-semibold text-slate-900">Connect your tracking</h2>
                <p class="mt-0.5 text-sm text-slate-500">Add your Meta Pixel ID and Google Analytics / Ads container ID so every donation tracked automatically.</p>
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
                <h2 class="text-base font-semibold text-slate-900">Add your elements</h2>
                <p class="mt-0.5 text-sm text-slate-500">Each element has its own embed code, ready to copy from the Elements page.</p>
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
                    <p class="text-xs text-slate-500">Each row has its own copy button — buttons/links get a <code class="rounded bg-slate-100 px-1 py-0.5 font-mono">data-ihsan</code> attribute, other elements get a full snippet.</p>
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
            <p class="mt-0.5 text-sm text-slate-500">Step-by-step for adding the loader to your website platform.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white divide-y divide-slate-100" x-data="{ open: null }">

            @php
                $guides = [
                    'html' => [
                        'label' => 'Custom HTML / Any website',
                        'icon_bg' => 'bg-orange-50',
                        'icon_color' => 'text-orange-500',
                        'steps' => [
                            'Open your website\'s HTML editor and find the <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">&lt;head&gt;</code> section.',
                            'Paste the loader snippet (Step 1 above) inside <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">&lt;head&gt;</code>.',
                            'Go to <strong>Elements</strong>, copy the embed code for each element, and place it where it belongs.',
                        ],
                    ],
                    'wp' => [
                        'label' => 'WordPress',
                        'icon_bg' => 'bg-blue-50',
                        'icon_color' => 'text-blue-600',
                        'steps' => [
                            'Install the <strong>Insert Headers and Footers</strong> plugin.',
                            'Go to <strong>Settings → Insert Headers and Footers</strong> and paste the loader snippet in the <em>Header</em> section.',
                            'On pages with donate buttons, edit the block and add <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">data-ihsan="TOKEN"</code> to your button HTML via a Custom HTML block.',
                        ],
                    ],
                    'webflow' => [
                        'label' => 'Webflow',
                        'icon_bg' => 'bg-indigo-50',
                        'icon_color' => 'text-indigo-600',
                        'steps' => [
                            'Go to <strong>Project Settings → Custom Code</strong> and paste the loader snippet in the <em>Head Code</em> section.',
                            'On your donate button element, open <strong>Element Settings → Custom Attributes</strong> and add attribute <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">data-ihsan</code> with value <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">TOKEN</code>.',
                            'Publish your site.',
                        ],
                    ],
                    'squarespace' => [
                        'label' => 'Squarespace',
                        'icon_bg' => 'bg-slate-100',
                        'icon_color' => 'text-slate-700',
                        'steps' => [
                            'Go to <strong>Website → Pages → Website Tools → Code Injection</strong> and paste the loader snippet in the <em>Header</em> section.',
                            'On your page, add a <strong>Code Block</strong> and paste the embed code copied from the Elements page.',
                        ],
                    ],
                    'wix' => [
                        'label' => 'Wix',
                        'icon_bg' => 'bg-amber-50',
                        'icon_color' => 'text-amber-600',
                        'steps' => [
                            'Go to <strong>Settings → Advanced → Custom Code</strong> and add the loader snippet to the <em>Head</em> section, loading on <em>All pages</em>.',
                            'To wire a donate button, go to your button in the editor → <strong>Settings → Link</strong> → set it to trigger <code class="rounded bg-slate-100 px-1 py-0.5 text-xs font-mono">Ihsan.open(\'TOKEN\')</code> via a code snippet on click, or use a Code Embed element.',
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
