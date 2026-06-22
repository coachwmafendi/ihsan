@php
    $campaign = $element?->campaign ?? $this->campaign;
    $organization = $campaign->organization;
    $oneTimeAmounts = $this->suggestedAmounts('one_time');
    $monthlyAmounts = $this->suggestedAmounts('monthly');
    $title = $this->config('title', 'Your most generous donation');
    $submitText = $this->config('submit_text', 'Donate and Support');
    $textColor = $this->config('text_color', '#212830');
    $backgroundColor = $this->config('background_color', '#FFFFFF');
    $iconColor = $this->config('icon_color', '#FF435A');
    $borderColor = $this->config('border_color', '#DEDFF3');
    $borderSize = (int) $this->config('border_size', 2);
    $borderRadius = (int) $this->config('border_radius', 6);
    $showShadow = (bool) $this->config('show_shadow', false);
    $allowMonthly = (bool) $this->config('allow_monthly', true);
    $showDedication = (bool) $this->config('show_dedication', false);
    $showComment = (bool) $this->config('show_comment', true);
    $showPhone = (bool) $this->config('show_phone', true);
    $isPopup = $this->isPopup;
    $isEmbed = $this->isEmbed;
    $isCompact = $isPopup || $isEmbed;
    $btnEffect = $this->config('button_effect', 'none');
    $btnEffectGradients = [
        'gradient_teal_green'    => 'linear-gradient(120deg,#0d9488,#14b8a6,#22c55e,#0d9488)',
        'gradient_blue_purple'   => 'linear-gradient(120deg,#2563eb,#7c3aed,#a855f7,#2563eb)',
        'gradient_orange_red'    => 'linear-gradient(120deg,#ea580c,#dc2626,#f97316,#ea580c)',
        'gradient_rose_pink'     => 'linear-gradient(120deg,#f43f5e,#ec4899,#fb7185,#f43f5e)',
        'gradient_amber_orange'  => 'linear-gradient(120deg,#f59e0b,#ea580c,#fbbf24,#f59e0b)',
        'gradient_cyan_blue'     => 'linear-gradient(120deg,#06b6d4,#2563eb,#38bdf8,#06b6d4)',
        'gradient_emerald_teal'  => 'linear-gradient(120deg,#10b981,#0d9488,#34d399,#10b981)',
        'gradient_indigo_purple' => 'linear-gradient(120deg,#6366f1,#7c3aed,#818cf8,#6366f1)',
        'gradient_gold_amber'    => 'linear-gradient(120deg,#eab308,#f59e0b,#fcd34d,#eab308)',
        'gradient_pink_purple'   => 'linear-gradient(120deg,#ec4899,#a855f7,#f472b6,#ec4899)',
    ];
    $btnHasEffect = $btnEffect !== 'none' && isset($btnEffectGradients[$btnEffect]);
    $btnEffectId  = 'ihsan-grad-' . $btnEffect;
    $usesSecureDonationTemplate = $this->config('template', 'secure-donation') === 'secure-donation';
    $usesSecureDonationShell = $usesSecureDonationTemplate && ! $isEmbed;
    $campaignImageRouteParameters = ['campaign' => $campaign];
    if ($isCompact) {
        $campaignImageRouteParameters['variant'] = 'modal';
    }
    $campaignImageUrl = filled($campaign->image_path)
        ? route('donations.campaign-image-campaign', $campaignImageRouteParameters)
        : null;
    $introTitle = filled($campaign->headline) ? $campaign->headline : $campaign->title;
    $introText = $campaign->description ?? '';
    $introTextPlain = strip_tags($introText);
    $introTruncated = str($introTextPlain)->limit(300)->toString();
    $isLongIntro = strlen($introTextPlain) > 300;
    $postDonationMode = $campaign?->config['post_donation_mode'] ?? 'default';
    $shareChannels = $campaign?->config['share_channels'] ?? ['facebook', 'x', 'linkedin', 'email'];
    $shareMessage = $campaign?->config['share_message'] ?? '';
    $redirectUrl = ($postDonationMode === 'redirect' && $this->isPublicPage)
        ? ($this->campaign?->redirect_url ?? $this->element?->campaign?->redirect_url)
        : null;
    $shareUrl = $campaign ? route('campaigns.public', $campaign) : request()->fullUrl();
    $connectedStripeAccountId = $organization->stripe_onboarded ? $organization->stripe_account_id : null;
    $currencySymbol = \App\Support\Currency::symbol($this->currency);
    $minimumAmount = (float) ($campaign->minimum_amount ?? 5);
    $totalAmount = (float) $this->amount + $this->estimatedFee;
@endphp

<div>
    @if($btnHasEffect)
        <style>
            @keyframes {{ $btnEffectId }} {
                0%   { background-position: 0% 50%; }
                50%  { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            .ihsan-submit-effect {
                background: {{ $btnEffectGradients[$btnEffect] }};
                background-size: 300% 300%;
                animation: {{ $btnEffectId }} 4s ease infinite;
            }
            .ihsan-submit-effect:hover { opacity: .9; }
        </style>
    @endif

    @if ($campaign->has_target && (float) $campaign->collected_amount >= (float) $campaign->target_amount)
        <div class="flex items-center gap-2 bg-emerald-600 px-4 py-2.5 text-center text-sm font-bold text-white">
            <span>🎉</span>
            <span>Target Tercapai! Terima kasih atas sokongan anda.</span>
        </div>
    @endif
    @if ($usesSecureDonationShell)
        @if ($isPopup)
            <div class="bg-white lg:grid lg:min-h-[680px] lg:grid-cols-[minmax(0,1fr)_440px] lg:items-stretch">
                <section class="hidden lg:flex lg:min-h-0 lg:flex-col lg:border-r lg:border-slate-200">
        @else
            <div class="{{ $isPublicPage ? 'min-h-0 bg-transparent px-0 py-0' : 'min-h-screen bg-[#eef1f6] px-4 py-8 sm:px-6 lg:px-8' }}">
                <main class="mx-auto w-full max-w-xl overflow-hidden rounded-2xl bg-white {{ $isPublicPage ? 'shadow-sm' : 'shadow-2xl' }}">
        @endif
            @if ($campaignImageUrl && ! $isPublicPage)
                <div class="p-2.5 pb-0 sm:p-3 sm:pb-0 {{ $isPopup ? 'lg:p-4 lg:pb-0' : '' }}">
                    <div
                        x-data="{ imageLoaded: false }"
                        x-init="$nextTick(() => { imageLoaded = $refs.readyImage?.complete && $refs.readyImage?.naturalWidth > 0 })"
                        data-ihsan-media-frame
                        class="relative overflow-hidden rounded-2xl bg-slate-100"
                    >
                        <div
                            x-show="! imageLoaded"
                            class="absolute inset-0 animate-pulse bg-gradient-to-r from-slate-100 via-slate-200 to-slate-100"
                        ></div>
                    <img
                        x-ref="readyImage"
                        src="{{ $campaignImageUrl }}"
                        alt="{{ $campaign->title }}"
                        data-ihsan-ready-media
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                        x-on:load="imageLoaded = true"
                        x-on:error="imageLoaded = true"
                        x-bind:class="imageLoaded ? 'opacity-100' : 'opacity-0'"
                        class="relative h-56 w-full rounded-2xl object-cover transition-opacity duration-200 sm:h-64 {{ $isPopup ? 'lg:h-[330px]' : '' }}"
                    />
                    </div>
                </div>
            @endif

            @if (! $isPublicPage)
            <div class="px-6 py-6 {{ $isPopup ? 'lg:flex lg:flex-1 lg:flex-col lg:justify-between lg:px-8 lg:py-7' : '' }}">
                <div>
                <div class="mb-5">
                    <p class="min-w-0 truncate text-xs font-bold uppercase tracking-[0.18em] text-slate-400">{{ $organization->name }}</p>
                </div>

                <h1 class="text-2xl font-bold tracking-normal text-slate-950">{{ $introTitle }}</h1>

                @if (filled($introText))
                    <div x-data="{ expanded: false }" class="mt-3 text-base/7 text-slate-600 text-justify">
                        <div x-show="! expanded" class="[&amp;>*]:inline [&amp;>p]:inline">
                            {{ $introTruncated }}{{ $isLongIntro ? '...' : '' }}
                        </div>
                        <div x-show="expanded" x-cloak>
                            {!! $introText !!}
                        </div>
                        @if ($isLongIntro)
                            <button type="button" x-on:click="expanded = ! expanded" class="mt-1 text-sm font-semibold text-teal-700 hover:text-teal-800">
                                <span x-show="! expanded">Read more &darr;</span>
                                <span x-show="expanded" x-cloak>Close &uarr;</span>
                            </button>
                        @endif
                    </div>
                @endif

                @if ($campaign->has_target)
                    @php
                        $donationFormRaised = (float) $campaign->collected_amount;
                        $donationFormTarget = (float) ($campaign->target_amount ?? 0);
                        $donationFormProgressPercent = $donationFormTarget > 0 ? min(100, round(($donationFormRaised / $donationFormTarget) * 100)) : 0;
                        $donationFormProgressWidth = $donationFormProgressPercent > 0 ? max(2, $donationFormProgressPercent) : 0;
                    @endphp
                    <div class="mt-5">
                        <div class="mb-1.5 flex items-end justify-between gap-4 text-xs">
                            <span class="font-semibold text-slate-950" x-text="currencySymbol + ' ' + formatCurrency(raisedAmount) + ' raised'">{{ $currencySymbol }} {{ number_format($donationFormRaised, 2) }} raised</span>
                            <span class="text-slate-500" x-text="'Goal ' + currencySymbol + ' ' + formatCurrency(targetAmount)">Goal {{ $currencySymbol }} {{ number_format($donationFormTarget, 2) }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-200">
                            <div class="h-3 rounded-full bg-teal-600" x-bind:style="{ width: progressWidth + '%' }" style="width: {{ $donationFormProgressWidth }}%"></div>
                        </div>
                    </div>
                @endif
                </div>
            </div>
            @endif

            @if ($isPopup)
                </section>
            @endif

            <section class="px-6 py-6 {{ $isPublicPage ? 'border-t-0' : 'border-t border-slate-200' }} {{ $isPopup ? 'lg:border-t-0 lg:px-7 lg:py-7' : '' }}">
                @if ($isPopup && $campaignImageUrl)
                    <div class="mb-5 -mx-6 -mt-6 lg:hidden">
                        <div
                            x-data="{ imageLoaded: false }"
                            x-init="$nextTick(() => { imageLoaded = $refs.readyImage?.complete && $refs.readyImage?.naturalWidth > 0 })"
                            data-ihsan-media-frame
                            class="relative overflow-hidden bg-slate-100"
                        >
                            <div
                                x-show="! imageLoaded"
                                class="absolute inset-0 animate-pulse bg-gradient-to-r from-slate-100 via-slate-200 to-slate-100"
                            ></div>
                        <img
                            x-ref="readyImage"
                            src="{{ $campaignImageUrl }}"
                            alt="{{ $campaign->title }}"
                            data-ihsan-ready-media
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                            x-on:load="imageLoaded = true"
                            x-on:error="imageLoaded = true"
                            x-bind:class="imageLoaded ? 'opacity-100' : 'opacity-0'"
                            class="relative h-48 w-full object-cover transition-opacity duration-200"
                        />
                        </div>
                    </div>
                @endif
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 5.25 6v5.25c0 4.2 2.86 8.1 6.75 9 3.89-.9 6.75-4.8 6.75-9V6L12 3.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 12.25 1.5 1.5 3-3.5" />
                        </svg>
                    </span>
                    <h2 class="text-xl font-bold tracking-normal text-slate-950">Secure donation</h2>
                </div>
    @elseif ($isEmbed)
        <div class="px-4 py-5 sm:px-5">
            @if (! $isPublicPage)
                <div class="mb-3">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-slate-500">{{ $organization->name }}</p>
                        <h1 class="text-sm font-semibold text-slate-950">{{ $campaign->title }}</h1>
                    </div>
                </div>

                @if ($campaign->has_target)
                    @php
                        $embedFormRaised = (float) $campaign->collected_amount;
                        $embedFormTarget = (float) ($campaign->target_amount ?? 0);
                        $embedFormProgressPercent = $embedFormTarget > 0 ? min(100, round(($embedFormRaised / $embedFormTarget) * 100)) : 0;
                        $embedFormProgressWidth = $embedFormProgressPercent > 0 ? max(2, $embedFormProgressPercent) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="mb-1 flex items-end justify-between gap-4 text-xs">
                            <span class="font-semibold text-slate-950" x-text="currencySymbol + ' ' + formatCurrency(raisedAmount) + ' raised'">{{ $currencySymbol }} {{ number_format($embedFormRaised, 2) }} raised</span>
                            <span class="text-slate-500" x-text="'Goal ' + currencySymbol + ' ' + formatCurrency(targetAmount)">Goal {{ $currencySymbol }} {{ number_format($embedFormTarget, 2) }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-200">
                            <div class="h-3 rounded-full bg-teal-700" x-bind:style="{ width: progressWidth + '%' }" style="width: {{ $embedFormProgressWidth }}%"></div>
                        </div>
                    </div>
                @endif
            @endif
    @elseif (! $isCompact)
        <div class="min-h-screen bg-[#eef1f6] px-4 py-8 sm:px-6 lg:px-8">
            <main class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[minmax(0,1fr)_440px]">
                <section class="rounded-xl border border-white/70 bg-white/80 p-6 shadow-sm backdrop-blur sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-medium text-slate-500">{{ $organization->name }}</p>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $campaign->title }}</h1>
                    </div>

                    @if ($campaign->description)
                        <div class="max-w-2xl text-base/7 text-slate-600 [&_p]:mb-4 [&_p:last-child]:mb-0">{!! $campaign->description !!}</div>
                    @endif

                    @if ($campaign->has_target)
                        @php
                            $targetAmount = max((float) $campaign->target_amount, 1);
                            $collectedAmount = (float) $campaign->collected_amount;
                            $progressPercent = min(100, round(($collectedAmount / $targetAmount) * 100));
                            $progressWidth = $progressPercent > 0 ? max(2, $progressPercent) : 0;
                        @endphp

                        <div class="mt-6 max-w-2xl">
                            <div class="mb-2 flex items-end justify-between gap-4 text-sm">
                                <span class="font-semibold text-slate-950">{{ $currencySymbol }} {{ number_format($collectedAmount, 2) }} raised</span>
                                <span class="text-slate-500">Goal {{ $currencySymbol }} {{ number_format($targetAmount, 2) }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-200">
                                <div class="h-3 rounded-full bg-teal-700" style="width: {{ $progressWidth }}%"></div>
                            </div>
                        </div>
                    @endif
                </section>

                <section class="mx-auto w-full max-w-[440px] rounded-[32px] bg-slate-200 p-3 shadow-xl shadow-slate-300/70 ring-1 ring-slate-300">
                    <div class="mx-auto mb-3 h-1.5 w-24 rounded-full bg-slate-300"></div>
    @endif

                <div
                    class="{{ $showShadow && ! $isPopup ? 'shadow-xl' : '' }} {{ $isPopup ? '' : ($isEmbed ? 'p-5 sm:p-6' : 'p-6') }}"
                    @if (! $isPopup)
                        style="background-color: {{ $backgroundColor }}; color: {{ $textColor }}; border: {{ $borderSize }}px solid {{ $borderColor }}; border-radius: {{ $isCompact ? $borderRadius : $borderRadius + 10 }}px;"
                    @endif
                >
                    <div
                        wire:ignore.self
                        x-data="donationStep(@js($name), @js($email), @js($phone), @js($connectedStripeAccountId), @js($minimumAmount), @js($this->amount), @js((int) request()->query('step', 1)), @js($frequency), @js($this->currency), @js($this->suggestedAmounts('one_time')), @js($this->suggestedAmounts('monthly')), @js(['myr' => 0.50, 'usd' => 0.30, 'sgd' => 0.50]), @js($this->coverFee), @js($this->isEmbed), @js($isPopup), @js($currencySymbol), @js($this->donationPublicId), @js($redirectUrl), @js($this->isPublicPage), @js($this->campaignCollectedAmount), @js($this->campaignTargetAmount))"
                        data-campaign-public-id="{{ $campaign->public_id }}"
                        x-init="$wire.trackServerPageView()"
                        class="relative"
                    >

                        {{-- Step progress indicator --}}
                        <div x-show="typeof currentStep === 'number'" class="mb-4 text-sm text-slate-500">
                            <span x-show="currentStep === 1" x-cloak>Step <strong class="text-slate-800">1</strong> of 3 — Choose Amount</span>
                            <span x-show="currentStep === 2" x-cloak>Step <strong class="text-slate-800">2</strong> of 3 — Your Details</span>
                            <span x-show="currentStep === 3" x-cloak>Step <strong class="text-slate-800">3</strong> of 3 — Payment</span>
                        </div>

                        {{-- Step 1: Amount & Frequency --}}
                        <div x-show="currentStep === 1" class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}">
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    x-on:click="selectFrequency('one_time')"
                                    class="min-h-10 rounded-lg border px-3 text-base font-semibold transition"
                                    :class="frequency === 'one_time' ? 'border-teal-600 bg-teal-200 text-teal-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                >
                                    Give once
                                </button>

                                @if ($allowMonthly)
                                    <button
                                        type="button"
                                        x-on:click="selectFrequency('monthly'); launchHearts($event)"
                                        class="relative min-h-10 rounded-lg border px-3 text-base font-semibold transition overflow-visible"
                                        :class="frequency === 'monthly' ? 'border-teal-600 bg-teal-200 text-teal-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                    >
                                        <span style="color: {{ $iconColor }};">&hearts;</span>
                                        Monthly
                                    </button>
                                @endif
                            </div>


                            @if ($this->config('show_suggested', true))
                                <div class="grid grid-cols-3 gap-2">
                                    <template x-for="amt in currentAmounts" :key="amountOptionKey(amt)">
                                        <button
                                            type="button"
                                            x-on:click="selectAmount(amt)"
                                            class="min-h-12 rounded-lg border px-2 text-base font-semibold transition"
                                            :class="isSelectedAmount(amt) ? 'border-teal-600 bg-teal-200 text-teal-700 shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                                            x-text="currencySymbol + ' ' + Number(amt).toLocaleString('en')"
                                        ></button>
                                    </template>
                                </div>
                            @endif

                            @if ($this->config('show_amount_input', true))
                                <label class="block">
                                    <span class="sr-only">Donation amount</span>
                                    <div class="flex min-h-14 items-center rounded-xl border border-slate-300 bg-white px-4 transition focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-600/20">
                                        <span class="{{ $usesSecureDonationShell ? 'text-2xl' : 'text-base' }} font-semibold text-slate-700">{{ $currencySymbol }}</span>
                                        <input
                                            x-ref="amountInput"
                                            x-bind:value="amount"
                                            type="text"
                                            inputmode="decimal"
                                            @keydown="
                                                const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Tab','Home','End'];
                                                if (allowed.includes($event.key)) return;
                                                const digitsDot = /^\d$/;
                                                if ($event.key === '.' && !$el.value.includes('.')) return;
                                                if (!digitsDot.test($event.key)) { $event.preventDefault(); return; }
                                                const parts = $el.value.split('.');
                                                const intDigits = parts[0].replace(/^0+/, '');
                                                if (parts.length === 1 && intDigits.length >= 5 && ($el.selectionStart <= ($el.value.length - ($el.selectionEnd - $el.selectionStart)))) $event.preventDefault();
                                                if (parts.length === 2 && parts[1].length >= 2 && $el.selectionStart > $el.value.indexOf('.')) $event.preventDefault();
                                            "
                                            @input="
                                                let v = $el.value.replace(/[^\d.]/g, '');
                                                const p = v.split('.');
                                                if (p.length > 2) v = p[0] + '.' + p.slice(1).join('');
                                                const intPart = p[0].replace(/^0+(?=\d)/, '');
                                                if (intPart.length > 5) v = intPart.slice(0, 5) + (p[1] !== undefined ? '.' + p[1] : '');
                                                if (p[1] !== undefined && p[1].length > 2) v = p[0] + '.' + p[1].slice(0, 2);
                                                if ($el.value !== v) $el.value = v;
                                                setAmount(v);
                                            "
                                            class="min-w-0 flex-1 border-0 bg-transparent px-2 text-3xl/none font-bold text-slate-950 outline-none placeholder:text-slate-300 sm:px-3"
                                        />
                                        @if (count($this->getAcceptedCurrencies()) > 1)
                                            @php $currencyDropdownLabels = ['myr' => 'RM', 'usd' => '$', 'sgd' => 'S$']; @endphp
                                            <div x-data="{ open: false }" class="relative flex items-center">
                                                <button
                                                    type="button"
                                                    x-on:click.stop="open = !open"
                                                    class="flex cursor-pointer select-none items-center gap-1.5"
                                                >
                                                    <x-currency-flag :currency="$this->currency" style="width:20px;height:14px"/>
                                                    <span class="text-sm font-medium text-slate-500">{{ strtoupper($this->currency) }}</span>
                                                    <svg class="mt-0.5 size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>
                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    x-on:click.outside="open = false"
                                                    class="absolute bottom-full right-0 z-20 mb-2 min-w-[90px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                                                >
                                                    @foreach ($this->getAcceptedCurrencies() as $code)
                                                        <button
                                                            type="button"
                                                            wire:click="selectCurrency('{{ $code }}')"
                                                            x-on:click="open = false"
                                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-semibold transition hover:bg-slate-50 {{ $this->currency === $code ? 'text-teal-700' : 'text-slate-700' }}"
                                                        >
                                                            <x-currency-flag :currency="$code" style="width:20px;height:14px"/>
                                                            <span>{{ $currencyDropdownLabels[$code] ?? strtoupper($code) }}</span>
                                                            <span class="text-xs font-normal text-slate-400">{{ strtoupper($code) }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-sm font-medium text-slate-500">{{ strtoupper($this->currency) }}</span>
                                        @endif
                                    </div>
                                    @if ($this->config('allow_cover_fee', true))
                                        <label class="mt-2 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-slate-300 hover:bg-slate-100"
                                            x-on:click.prevent="coverFee = !coverFee"
                                        >
                                            <span
                                                :class="coverFee ? 'border-teal-600 bg-teal-600' : 'border-slate-500 bg-white'"
                                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded border-2 transition"
                                            >
                                                <svg x-show="coverFee" class="size-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                            <span class="flex flex-col gap-0.5">
                                                <span class="text-sm font-medium text-slate-700">
                                                    I'll cover the processing fee
                                                    <span class="text-teal-700" x-text="`(+${currencySymbol}${(parseFloat(amount) > 0 ? (parseFloat(amount) * 0.03 + fixedFee).toFixed(2) : '0.00')})`"></span>
                                                </span>
                                                <span class="text-xs text-slate-400">Help ensure 100% of your donation reaches us.</span>
                                            </span>
                                        </label>
                                    @endif
                                    <div x-show="stepErrors.amount" x-cloak class="mt-1 text-xs text-red-600" x-text="stepErrors.amount"></div>
                                </label>
                            @endif

                             <button
                                 type="button"
                                 x-on:click="nextStep()"
                                 x-bind:disabled="processing"
                                 class="min-h-12 w-full rounded-lg px-4 text-base font-bold text-white shadow-sm transition active:scale-[0.98] disabled:opacity-60 {{ $btnHasEffect ? 'ihsan-submit-effect' : 'bg-teal-600 hover:bg-teal-700' }}"
                             >
                                 {{ $isEmbed ? $this->config('button_text', 'Continue') : 'Continue' }} &rarr;
                             </button>
                         </div>{{-- end Step 1 --}}

                         {{-- Step 2: Donor Details --}}
                         <div x-show="currentStep === 2" x-cloak class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}">

                             <button
                                 type="button"
                                 x-on:click="prevStep()"
                                 x-bind:disabled="processing"
                                 class="mb-1 flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition disabled:opacity-50"
                             >
                                 <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                 Back
                             </button>

                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Your details</p>

                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-slate-700">Name</span>
                                    <input
                                        wire:model="name"
                                        x-model="donorName"
                                        type="text"
                                        autocomplete="name"
                                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                                        placeholder="Your full name"
                                    />
                                    <div x-show="stepErrors.name" x-cloak class="mt-1 text-sm text-red-600" x-text="stepErrors.name"></div>
                                    @error('name')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-slate-700">Email</span>
                                    <input
                                        wire:model="email"
                                        x-model="donorEmail"
                                        type="email"
                                        autocomplete="email"
                                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                                        placeholder="you@example.com"
                                    />
                                    <div x-show="stepErrors.email" x-cloak class="mt-1 text-sm text-red-600" x-text="stepErrors.email"></div>
                                    @error('email')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                                </label>

                                @if ($showPhone)
                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-slate-700">Phone <span class="font-normal text-slate-400">(optional)</span></span>
                                        <input
                                            wire:model="phone"
                                            x-model="donorPhone"
                                            type="tel"
                                            autocomplete="tel"
                                            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                                            placeholder="012-345 6789"
                                        />
                                    </label>
                                @endif
                            </div>

                            @if ($showDedication)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                    <input wire:model="dedicate" type="checkbox" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600" />
                                    Dedicate this donation
                                </label>
                            @endif

                            @if ($showComment)
                                <label class="block">
                                    <span class="mb-0.5 block text-sm font-medium text-slate-700">Comment <span class="font-normal text-slate-400">(optional)</span></span>
                                    <textarea wire:model="comment" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/10" placeholder="Leave a message..."></textarea>
                                </label>
                            @endif

                             <button
                                 type="button"
                                 x-on:click="nextStep()"
                                 x-bind:disabled="processing"
                                 class="min-h-12 w-full rounded-lg px-4 text-base font-bold text-white shadow-sm transition active:scale-[0.98] disabled:opacity-60 {{ $btnHasEffect ? 'ihsan-submit-effect' : 'bg-teal-600 hover:bg-teal-700' }}"
                             >
                                 Continue &rarr;
                             </button>
                         </div>{{-- end Step 2 --}}

                         {{-- Step 3: Payment --}}
                         <div x-show="currentStep === 3" x-cloak class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}">

                             <button
                                 type="button"
                                 x-on:click="prevStep()"
                                 x-bind:disabled="processing"
                                 class="mb-1 flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition disabled:opacity-50"
                             >
                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                Back
                            </button>

                            {{-- Summary bar --}}
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm">
                                <span class="font-semibold text-slate-800"
                                    x-text="currencySymbol + ' ' + (parseFloat(amount) + (coverFee ? parseFloat(amount) * 0.03 + fixedFee : 0)).toFixed(2)"
                                ></span>
                                <span class="text-slate-500" x-text="frequency === 'monthly' ? 'Monthly' : 'One-time'"></span>
                            </div>

                            <div wire:ignore>
                                <label class="mb-0.5 block text-sm font-medium text-slate-700">Payment details</label>
                                <div id="payment-element" class="min-h-10 rounded-lg border border-slate-200 px-3 py-2.5 transition focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-600/10"></div>
                                <div x-show="cardError" x-cloak class="mt-1 text-sm text-red-600" x-text="cardError"></div>
                            </div>

                            <form @submit.prevent="handleSubmit">
                                <button
                                    type="submit"
                                    class="min-h-12 w-full rounded-lg px-4 text-sm font-bold text-white shadow-sm transition active:scale-[0.98] disabled:opacity-60 {{ $btnHasEffect ? 'ihsan-submit-effect' : 'bg-teal-600 hover:bg-teal-700' }}"
                                    x-bind:disabled="processing"
                                >
                                    @if ($usesSecureDonationShell && in_array($submitText, ['Donate and Support', 'Donate Now'], true))
                                        <span x-show="!processing" x-text="frequency === 'monthly' ? 'Donate monthly' : 'Donate once'"></span>
                                    @else
                                        <span x-show="!processing">{{ $submitText }}</span>
                                    @endif
                                    <span x-show="processing" x-cloak>Processing...</span>
                                </button>
                            </form>
                        </div>{{-- end Step 3 --}}

                        {{-- Processing overlay --}}
                        <div
                            x-show="processing"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="absolute inset-0 z-30 flex flex-col items-center justify-center rounded-xl bg-white/95 px-4 text-center backdrop-blur-sm"
                        >
                            <div class="flex size-16 items-center justify-center rounded-full bg-teal-50">
                                <svg class="size-7 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </div>
                            <h2 class="mt-4 text-lg font-semibold text-slate-900">Processing payment...</h2>
                            <p class="mt-1 text-sm text-slate-500">Please wait while we process your donation.</p>
                        </div>

                        {{-- Success --}}
                        <div x-show="currentStep === 'success'" x-cloak class="py-10 text-center">
                            <div class="mx-auto mb-5 flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-emerald-100 ring-8 ring-emerald-50">
                                <svg class="size-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </div>
                            <h2 class="text-lg font-semibold text-slate-900">Thank you, <span x-text="donorName"></span>!</h2>
                            <p class="mt-1 text-sm text-slate-500">Receipt sent to <span x-text="donorEmail"></span>.</p>
                            <p class="mt-1 text-sm text-slate-500">{{ ($this->campaign ?? $this->element?->campaign)?->thank_you_message ?: $this->config('success_message', 'Thank you for your donation!') }}</p>

                            @if ($postDonationMode !== 'redirect' && ! empty($shareChannels))
                                <div class="mt-6">
                                    <p class="mb-3 text-sm font-semibold text-slate-700">Share this campaign</p>
                                    <div class="flex items-center justify-center gap-3">
                                        @if (in_array('facebook', $shareChannels, true))
                                            <a
                                                href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                                                target="_blank" rel="noopener noreferrer"
                                                aria-label="Share on Facebook"
                                                class="inline-flex items-center justify-center size-9 rounded-full bg-slate-100 text-slate-600 hover:bg-teal-100 hover:text-teal-700"
                                            >
                                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                                                </svg>
                                            </a>
                                        @endif

                                        @if (in_array('x', $shareChannels, true))
                                            <a
                                                href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareMessage) }}"
                                                target="_blank" rel="noopener noreferrer"
                                                aria-label="Share on X"
                                                class="inline-flex items-center justify-center size-9 rounded-full bg-slate-100 text-slate-600 hover:bg-teal-100 hover:text-teal-700"
                                            >
                                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                                </svg>
                                            </a>
                                        @endif

                                        @if (in_array('linkedin', $shareChannels, true))
                                            <a
                                                href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
                                                target="_blank" rel="noopener noreferrer"
                                                aria-label="Share on LinkedIn"
                                                class="inline-flex items-center justify-center size-9 rounded-full bg-slate-100 text-slate-600 hover:bg-teal-100 hover:text-teal-700"
                                            >
                                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z" />
                                                    <rect x="2" y="9" width="4" height="12" />
                                                    <circle cx="4" cy="4" r="2" />
                                                </svg>
                                            </a>
                                        @endif

                                        @if (in_array('email', $shareChannels, true))
                                            <a
                                                href="mailto:?subject={{ urlencode('Support this campaign') }}&body={{ urlencode($shareMessage."\n\n".$shareUrl) }}"
                                                aria-label="Share by email"
                                                class="inline-flex items-center justify-center size-9 rounded-full bg-slate-100 text-slate-600 hover:bg-teal-100 hover:text-teal-700"
                                            >
                                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($isPopup)
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close-popup'); window.parent.postMessage({type:'ihsan:close-modal'}, '*')"
                                    class="mt-6 w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                >
                                    Close
                                </button>
                            @endif
                        </div>

                        {{-- Error --}}
                        <div x-show="currentStep === 'error'" x-cloak class="py-8 text-center">
                            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-red-50">
                                <svg class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-slate-900">Payment failed</h2>
                            <p class="mt-1 text-sm text-slate-500" x-text="cardError"></p>
                            <button
                                type="button"
                                x-on:click="currentStep = 3"
                                class="mt-4 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700"
                            >
                                Try again
                            </button>
                        </div>
                    </div>
                </div>

    @if ($usesSecureDonationShell)
            </section>
        @if ($isPopup)
            </div>
        @else
                </main>
            </div>
        @endif
    @elseif ($isEmbed)
        </div>
    @else
                    <div class="mx-auto mt-4 size-7 rounded-full bg-slate-300"></div>
                </section>
            </main>
        </div>
    @endif
</div>

@script
{{-- donationStep Alpine component registered in layouts/donation.blade.php via alpine:init --}}
<script>
    Alpine.data('donationStep', (initialName = '', initialEmail = '', initialPhone = '', connectedStripeAccountId = null, initialMinimumAmount = 5, initialAmount = 5, initialStep = 1, initialFrequency = 'one_time', initialCurrency = 'myr', initialOneTimeAmounts = [], initialMonthlyAmounts = [], initialFeeConfig = {myr: 0.50, usd: 0.30, sgd: 0.50}, initialCoverFee = true, initialIsEmbed = false, initialIsPopup = false, initialCurrencySymbol = 'RM', initialDonationPublicId = null, initialRedirectUrl = '', initialIsPublicPage = false, initialRaisedAmount = 0, initialTargetAmount = 0) => {
        let stripe = null;
        let elements = null;
        let paymentElement = null;

        return {
            amount: String(initialAmount ?? ''),
            currency: initialCurrency,
            currencySymbol: initialCurrencySymbol,
            frequency: initialFrequency,
            oneTimeAmounts: initialOneTimeAmounts,
            monthlyAmounts: initialMonthlyAmounts,
            donorName: initialName,
            donorEmail: initialEmail,
            donorPhone: initialPhone,
            minimumAmount: initialMinimumAmount,
            feeConfig: initialFeeConfig,
            coverFee: initialCoverFee,
            isEmbed: initialIsEmbed,
            isPopup: initialIsPopup,
            isPublicPage: initialIsPublicPage,
            donationPublicId: initialDonationPublicId,
            redirectUrl: initialRedirectUrl,
            campaignPublicId: '',
            raisedAmount: initialRaisedAmount,
            targetAmount: initialTargetAmount,
            processing: false,
            currentStep: initialStep > 1 ? initialStep : 1,
            stepErrors: {},
            cardError: '',

            get fixedFee() {
                return this.feeConfig[this.currency] ?? 0.50;
            },

            get currentAmounts() {
                return this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
            },

            get progressWidth() {
                if (!this.targetAmount || this.targetAmount <= 0) {
                    return 0;
                }

                const percent = Math.round((this.raisedAmount / this.targetAmount) * 100);

                return percent > 0 ? Math.max(2, Math.min(100, percent)) : 0;
            },

            formatCurrency(value) {
                return Number(value || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            amountNumber(value = this.amount) {
                const parsed = parseFloat(value);

                return Number.isFinite(parsed) ? parsed : null;
            },

            amountOptionKey(amt) {
                return `${this.currency}-${this.frequency}-${amt}`;
            },

            isSelectedAmount(amt) {
                const selected = this.amountNumber();
                const option = this.amountNumber(amt);

                return selected !== null && option !== null && selected === option;
            },

            setAmount(value) {
                this.amount = String(value ?? '');

                this.$nextTick(() => {
                    if (this.$refs.amountInput && this.$refs.amountInput.value !== this.amount) {
                        this.$refs.amountInput.value = this.amount;
                    }
                });
            },

            selectAmount(amt) {
                this.setAmount(amt);
            },

            selectFrequency(freq) {
                this.frequency = freq;
                const amounts = freq === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
                const amount = amounts.length > 0 ? amounts[0] : this.amount;
                this.setAmount(amount);
            },

            launchHearts(event) {
                const btn = event.currentTarget;
                const rect = btn.getBoundingClientRect();
                const color = btn.querySelector('span') ? getComputedStyle(btn.querySelector('span')).color : '#e11d48';
                const colors = [color, '#f43f5e', '#fb7185', '#fda4af'];
                const count = 5;
                for (let i = 0; i < count; i++) {
                    setTimeout(() => {
                        const el = document.createElement('span');
                        el.textContent = '♥';
                        const size = 12 + Math.random() * 10;
                        const startX = rect.left + rect.width * 0.2 + Math.random() * rect.width * 0.6;
                        const startY = rect.top;
                        const driftX = (Math.random() - 0.5) * 50;
                        const riseY = 60 + Math.random() * 60;
                        const duration = 1000 + Math.random() * 600;
                        el.style.cssText = `
                            position: fixed;
                            left: ${startX}px;
                            top: ${startY}px;
                            color: ${colors[Math.floor(Math.random() * colors.length)]};
                            font-size: ${size}px;
                            pointer-events: none;
                            z-index: 9999;
                            user-select: none;
                        `;
                        document.body.appendChild(el);
                        el.animate([
                            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
                            { transform: `translate(${driftX * 0.5}px, ${-riseY * 0.5}px) scale(1.1)`, opacity: 0.8, offset: 0.4 },
                            { transform: `translate(${driftX}px, ${-riseY}px) scale(0.5)`, opacity: 0 },
                        ], { duration, easing: 'ease-out', fill: 'forwards' }).onfinish = () => el.remove();
                    }, i * 80);
                }
            },

            validateStep1() {
                this.stepErrors = {};
                const amt = parseFloat(this.amount);
                if (!amt || amt < this.minimumAmount) {
                    this.stepErrors.amount = 'Minimum amount is ' + this.minimumAmount + '.';
                    return false;
                }
                if (amt > 100000) {
                    this.stepErrors.amount = 'Amount cannot exceed 100,000.';
                    return false;
                }
                return true;
            },

            validateStep2() {
                this.stepErrors = {};
                let valid = true;
                if (!this.donorName.trim()) {
                    this.stepErrors.name = 'Name is required.';
                    valid = false;
                }
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.donorEmail)) {
                    this.stepErrors.email = 'Please enter a valid email address.';
                    valid = false;
                }
                return valid;
            },

            mountPaymentElement() {
                const container = document.getElementById('payment-element');
                if (!container || container.hasChildNodes()) return;
                const amount = Math.round(parseFloat(this.amount || 0) * 100);
                const isRecurring = this.frequency === 'monthly';
                elements = stripe.elements({
                    mode: isRecurring ? 'subscription' : 'payment',
                    amount: amount,
                    currency: this.currency,
                    setupFutureUsage: isRecurring ? 'off_session' : undefined,
                    locale: 'ms',
                    appearance: {
                        theme: 'stripe',
                        variables: {
                            colorPrimary: '#0d9488',
                            fontSizeBase: '15px',
                        },
                    },
                });
                paymentElement = elements.create('payment', {
                    layout: 'tabs',
                    wallets: {
                        link: 'never',
                    },
                    defaultValues: {
                        billingDetails: {
                            name: this.donorName || undefined,
                            email: this.donorEmail || undefined,
                        },
                    },
                });
                paymentElement.mount('#payment-element');
                paymentElement.on('change', (event) => {
                    this.cardError = event.error ? event.error.message : '';
                });
            },

            nextStep() {
                if (this.currentStep === 1 && !this.validateStep1()) return;
                if (this.currentStep === 2 && !this.validateStep2()) return;
                if (typeof this.currentStep !== 'number' || this.currentStep >= 3) return;

                // Embed step 1: hand off to parent modal instead of advancing in iframe
                if (this.isEmbed && ! this.isPublicPage && this.currentStep === 1) {
                    var embedToken = window.location.pathname.split('/').filter(Boolean).pop();
                    window.parent.postMessage({
                        type: 'ihsan:step-continue',
                        token: embedToken,
                        amount: this.amount,
                        frequency: this.frequency,
                        currency: this.currency,
                        coverFee: this.coverFee ? 1 : 0,
                    }, '*');
                    return;
                }

                this.currentStep++;
                if (this.currentStep === 2) {
                    this.trackInitiateCheckout();
                }
                if (this.currentStep === 3) {
                    this.$nextTick(() => this.mountPaymentElement());
                }
            },

            trackInitiateCheckout() {
                if (this._initiateSent) {
                    return;
                }
                this._initiateSent = true;

                const amountNumber = parseFloat(this.amount);
                if (!Number.isFinite(amountNumber) || amountNumber <= 0) {
                    return;
                }

                if (typeof window.IhsanTrack === 'function') {
                    window.IhsanTrack('InitiateCheckout', {
                        value: amountNumber,
                        currency: this.currency.toUpperCase(),
                        content_type: 'product',
                        contents: [{
                            id: 'donation',
                            quantity: 1,
                            item_price: amountNumber,
                        }],
                    });
                }

                if (this.$wire && typeof this.$wire.trackServerInitiateCheckout === 'function') {
                    this.$wire.trackServerInitiateCheckout();
                }
            },

            trackPurchase() {
                if (typeof window.IhsanTrack !== 'function') {
                    return;
                }

                if (!this.donationPublicId) {
                    return;
                }

                const amountNumber = parseFloat(this.amount);
                if (!Number.isFinite(amountNumber) || amountNumber <= 0) {
                    return;
                }

                window.IhsanTrack('Purchase', {
                    value: amountNumber,
                    currency: this.currency.toUpperCase(),
                    content_type: 'product',
                    contents: [{
                        id: 'donation',
                        quantity: 1,
                        item_price: amountNumber,
                    }],
                }, {
                    eventID: 'purchase_' + this.donationPublicId,
                });
            },

            prevStep() {
                if (this.currentStep > 1) this.currentStep--;
            },

            async init() {
                this.campaignPublicId = this.$el.dataset.campaignPublicId || '';
                this.raisedAmount = parseFloat($wire.campaignCollectedAmount) || 0;
                this.targetAmount = parseFloat($wire.campaignTargetAmount) || 0;

                $wire.on('amount-updated', ({ amount }) => {
                    this.setAmount(amount);
                });

                $wire.on('currency-updated', ({ currency, symbol, amount, oneTimeAmounts, monthlyAmounts }) => {
                    if (currency) this.currency = currency;
                    this.currencySymbol = symbol;
                    if (oneTimeAmounts) this.oneTimeAmounts = oneTimeAmounts;
                    if (monthlyAmounts) this.monthlyAmounts = monthlyAmounts;
                    const amounts = this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
                    this.setAmount(amount ?? (amounts.length > 0 ? amounts[0] : this.amount));
                });

                stripe = connectedStripeAccountId
                    ? Stripe(window.stripePublishableKey, { stripeAccount: connectedStripeAccountId })
                    : Stripe(window.stripePublishableKey);

                if (this.isPopup || this.isEmbed) {
                    await this.waitForReadyMedia();
                    await this.waitForReadyPaint();

                    requestAnimationFrame(() => {
                        window.parent.postMessage({ type: 'ihsan:donation-ready' }, '*');
                    });
                }
            },

            waitForReadyPaint() {
                return new Promise((resolve) => {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(resolve);
                    });
                });
            },

            waitForReadyMedia() {
                const images = Array.from(this.$root.querySelectorAll('[data-ihsan-ready-media]'));

                if (images.length === 0) {
                    return Promise.resolve();
                }

                const waits = images.map((image) => {
                    if (image.complete && image.naturalWidth > 0) {
                        return image.decode ? image.decode().catch(() => {}) : Promise.resolve();
                    }

                    return new Promise((resolve) => {
                        const finish = () => {
                            image.removeEventListener('load', finish);
                            image.removeEventListener('error', finish);
                            resolve();
                        };

                        image.addEventListener('load', finish, { once: true });
                        image.addEventListener('error', finish, { once: true });
                    });
                });

                return Promise.race([
                    Promise.all(waits),
                    new Promise((resolve) => setTimeout(resolve, 8000)),
                ]);
            },

            async handleSubmit() {
                if (this.processing) return;
                this.processing = true;
                this.cardError = '';

                const { error: submitError } = await elements.submit();
                if (submitError) {
                    this.processing = false;
                    this.currentStep = 'error';
                    this.cardError = submitError.message;
                    return;
                }

                $wire.$set('frequency', this.frequency, false);
                $wire.$set('amount', this.amount, false);
                $wire.$set('coverFee', this.coverFee, false);
                $wire.$set('name', this.donorName, false);
                $wire.$set('email', this.donorEmail, false);
                $wire.$set('phone', this.donorPhone, false);

                let clientSecret;
                try {
                    clientSecret = await $wire.submit();
                } catch (e) {
                    this.processing = false;
                    this.currentStep = 'error';
                    this.cardError = 'Unable to start payment. Please try again.';
                    return;
                }

                if (!clientSecret) {
                    this.processing = false;
                    this.currentStep = 'error';
                    this.cardError = 'Unable to start payment. Please try again.';
                    return;
                }

                const paymentIntentId = clientSecret.split('_secret_')[0] ?? null;

                const { error: confirmError } = await stripe.confirmPayment({
                    elements,
                    clientSecret,
                    confirmParams: {
                        receipt_email: this.donorEmail,
                        return_url: window.location.href,
                    },
                    redirect: 'if_required',
                });

                if (confirmError) {
                    this.processing = false;
                    this.currentStep = 'error';
                    this.cardError = confirmError.message;
                    return;
                }

                if (paymentIntentId) {
                    try {
                        await $wire.confirmPayment(paymentIntentId);
                    } catch (e) {
                        // Server finalization failure should not block the success UX.
                    }
                }

                this.donationPublicId = $wire.donationPublicId;
                this.processing = false;
                this.trackPurchase();

                if ($wire.campaignCollectedAmount !== undefined) {
                    this.raisedAmount = parseFloat($wire.campaignCollectedAmount) || 0;
                }

                this.currentStep = 'success';

                if (this.campaignPublicId && window.parent !== window) {
                    window.parent.postMessage({ type: 'ihsan:donation-success', campaignPublicId: this.campaignPublicId }, '*');
                }

                if (this.redirectUrl && ! this.isEmbed) {
                    setTimeout(() => { window.location.href = this.redirectUrl; }, 1500);
                }
            },
        };
    });
</script>
@endscript
