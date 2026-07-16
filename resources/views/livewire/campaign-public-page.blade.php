@php
$config = $campaign->config ?? [];
$showTotalRaised = $config['show_total_raised'] ?? true;
$contentLogo = $config['content_logo'] ?? $campaign->organization->logo_path;
$contentImage = $config['content_image'] ?? $campaign->image_path;
$contentTitle = $config['content_title'] ?? $campaign->title;
$contentMessage = $config['content_message'] ?? $campaign->description;
$messageText = strip_tags((string) $contentMessage);
$messageIsLong = mb_strlen($messageText) > 200;
$messageShort = Illuminate\Support\Str::limit($messageText, 200);
@endphp

<div
    x-data="{ loaded: false }"
    x-init="$nextTick(() => setTimeout(() => loaded = true, 350))"
    class="relative"
>
    {{-- Loading skeleton --}}
    <div
        x-show="! loaded"
        x-transition.opacity.duration.250ms
        x-cloak
        aria-hidden="true"
        class="absolute inset-0 z-10 bg-[#eef1f6]"
    >
        <main class="max-w-6xl mx-auto px-4 py-12">
            {{-- Header --}}
            <div class="mb-8">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 shrink-0 animate-pulse rounded-full bg-slate-200"></div>
                    <div class="h-4 w-40 animate-pulse rounded bg-slate-200"></div>
                </div>
                <div class="mt-4 h-8 w-3/4 max-w-xl animate-pulse rounded bg-slate-200"></div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                {{-- Left column skeleton --}}
                <div class="space-y-6">
                    @if ($showTotalRaised)
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-end justify-between gap-4">
                                <div class="space-y-2">
                                    <div class="h-3 w-14 animate-pulse rounded bg-slate-200"></div>
                                    <div class="h-7 w-32 animate-pulse rounded bg-slate-200"></div>
                                </div>
                                <div class="h-3 w-24 animate-pulse rounded bg-slate-200"></div>
                            </div>
                            <div class="mt-6 h-3 w-full animate-pulse rounded-full bg-slate-200"></div>
                            <div class="mt-5 h-16 w-full animate-pulse rounded-lg bg-slate-200"></div>
                        </div>
                    @endif

                    @if ($contentImage)
                        <div class="h-96 w-full animate-pulse rounded-xl bg-slate-200"></div>
                    @endif

                    @if ($contentMessage)
                        <div class="space-y-3">
                            <div class="h-4 w-full animate-pulse rounded bg-slate-200"></div>
                            <div class="h-4 w-full animate-pulse rounded bg-slate-200"></div>
                            <div class="h-4 w-5/6 animate-pulse rounded bg-slate-200"></div>
                            <div class="h-4 w-4/5 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    @endif
                </div>

                {{-- Right column skeleton --}}
                <div class="md:sticky md:top-8 md:self-start lg:pl-4">
                    <div class="h-[32rem] w-full animate-pulse rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="space-y-5">
                            <div class="h-6 w-1/2 animate-pulse rounded bg-slate-200"></div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                                <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                                <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                            </div>
                            <div class="h-12 w-full animate-pulse rounded-lg bg-slate-200"></div>
                            <div class="h-12 w-full animate-pulse rounded-lg bg-slate-200"></div>
                            <div class="h-12 w-full animate-pulse rounded-lg bg-slate-200"></div>
                            <div class="h-24 w-full animate-pulse rounded-lg bg-slate-200"></div>
                            <div class="h-12 w-full animate-pulse rounded-lg bg-slate-200"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-12">
    <div class="mb-8">
        <div class="flex items-center gap-3">
            @if ($contentLogo)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contentLogo) }}"
                    alt="{{ $campaign->organization->name }}"
                    class="h-10 object-contain"
                />
            @endif
            <p class="text-sm font-medium text-slate-500">{{ $campaign->organization->name }}</p>
        </div>

        <div class="mt-4">
            <h1 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
                {{ $contentTitle }}
            </h1>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Left column: campaign details --}}
        <div class="space-y-6">
            @if ($showTotalRaised)
                @php
                    [
                        'raised' => $raised,
                        'target' => $target,
                        'progressPercent' => $progressPercent,
                        'checkpoints' => $checkpoints,
                        'segments' => $segments,
                        'currentCheckpointIndex' => $currentCheckpointIndex,
                        'currentCheckpointAmount' => $currentCheckpointAmount,
                        'leftToNext' => $leftToNext,
                        'goalReached' => $goalReached,
                    ] = $this->progress;
                @endphp

                <div wire:poll.10s="pollCampaign" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <style>
                        @keyframes sparkle-fade {
                            from { opacity: 0; transform: scale(0.8); }
                            to { opacity: 0.8; transform: scale(1); }
                        }
                    </style>
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-slate-500">Raised</p>
                            <p class="text-2xl font-bold text-slate-900">RM {{ number_format($raised, 2) }}</p>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Goal RM {{ number_format($target, 2) }}</p>
                    </div>

                    @if ($target > 0)
                        <div class="mt-6">
                            <div class="relative mb-10">
                                {{-- Percentage label --}}
                                <span
                                    class="absolute -top-5 text-xs font-extrabold text-emerald-600 transition-all duration-1000 ease-out motion-reduce:transition-none {{ $progressPercent >= 100 ? 'right-0 scale-110' : '-translate-x-1/2' }}"
                                    style="{{ $progressPercent >= 100 ? 'right: 0' : 'left: ' . $progressPercent . '%' }}"
                                >
                                    {{ number_format($progressPercent, 1) }}%
                                </span>

                                {{-- Continuous milestone track --}}
                                <div class="relative h-3 rounded-full bg-slate-200">
                                    <div
                                        class="absolute left-0 top-0 h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-1000 ease-out motion-reduce:transition-none"
                                        style="width: {{ $progressPercent }}%"
                                    ></div>

                                    {{-- Segment dividers --}}
                                    @foreach ($segments as $segment)
                                        @php
                                            $dividerPosition = ($segment['end'] / $target) * 100;
                                        @endphp
                                        <div
                                            class="absolute top-0 h-full w-0.5 bg-white"
                                            style="left: {{ $dividerPosition }}%"
                                        ></div>
                                    @endforeach

                                    {{-- Decorative celebratory icons --}}
                                    @if ($progressPercent >= 10)
                                        <span class="pointer-events-none absolute -top-4 left-[10%] -translate-x-1/2 text-[0.6rem] opacity-0 motion-reduce:opacity-80 animate-[sparkle-fade_0.4s_ease-out_1000ms_forwards] motion-reduce:animate-none" aria-hidden="true">✨</span>
                                    @endif
                                    @if ($progressPercent >= 25)
                                        <span class="pointer-events-none absolute -top-4 left-[25%] -translate-x-1/2 text-[0.6rem] opacity-0 motion-reduce:opacity-80 animate-[sparkle-fade_0.4s_ease-out_1150ms_forwards] motion-reduce:animate-none" aria-hidden="true">✨</span>
                                    @endif
                                    @if ($progressPercent >= 50)
                                        <span class="pointer-events-none absolute -top-4 left-[50%] -translate-x-1/2 text-[0.6rem] opacity-0 motion-reduce:opacity-80 animate-[sparkle-fade_0.4s_ease-out_1300ms_forwards] motion-reduce:animate-none" aria-hidden="true">✨</span>
                                    @endif
                                    @if ($progressPercent >= 100)
                                        <span class="pointer-events-none absolute -top-14 right-2 text-sm" aria-hidden="true">🏁</span>
                                    @endif
                                </div>

                                {{-- Checkpoint dots --}}
                                @foreach ($checkpoints as $index => $amount)
                                    @php
                                        $position = ($amount / $target) * 100;
                                        $isCompleted = $index > 0 && $raised >= $amount;
                                        $isCurrent = $index === $currentCheckpointIndex;
                                    @endphp
                                    <div
                                        class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"
                                        style="left: {{ $position }}%"
                                    >
                                        @if ($isCurrent)
                                            <span class="block size-5 animate-pulse rounded-full border-[3px] border-white bg-emerald-500 shadow-md ring-2 ring-emerald-400 motion-reduce:animate-none"></span>
                                        @elseif ($isCompleted || $index === 0)
                                            <span class="block size-4 rounded-full border-2 border-white bg-emerald-500 shadow-md"></span>
                                        @else
                                            <span class="block size-4 rounded-full border-2 border-white bg-slate-400 shadow-md"></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Checkpoint labels --}}
                            <div class="mt-4 flex items-start justify-between gap-2 text-xs font-semibold leading-none">
                                @foreach ($checkpoints as $index => $amount)
                                    @php
                                        $isCurrent = $index === $currentCheckpointIndex;
                                        $label = match ($index) {
                                            0 => 'RM0',
                                            default => 'RM'.number_format($amount / 1000, 0).'K',
                                        };
                                        $baseClasses = $index === 0
                                            ? 'shrink-0 text-left'
                                            : ($index === count($checkpoints) - 1 ? 'shrink-0 text-right' : 'flex-1 text-center');
                                    @endphp
                                    <span class="block {{ $baseClasses }} {{ $isCurrent ? 'rounded-full bg-emerald-100 px-2 py-1 text-emerald-700 ring-1 ring-emerald-200' : 'text-slate-400' }}">
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Next milestone callout --}}
                        <div class="mt-5 rounded-lg border border-emerald-100 bg-emerald-50 p-3">
                            @if ($goalReached)
                                <p class="text-sm font-extrabold text-emerald-800">🎉 Goal smashed!</p>
                                <p class="mt-0.5 text-xs font-medium text-emerald-600">We crossed the target. Let’s keep the momentum going!</p>
                            @else
                                <p class="text-xs font-medium text-emerald-700">Next milestone: RM {{ number_format($currentCheckpointAmount, 2) }}</p>
                                <p class="mt-1 text-sm font-semibold text-emerald-900">RM {{ number_format($leftToNext, 2) }} left to reach next milestone</p>
                                <p class="mt-1 text-xs text-emerald-600">“Almost there! Help us reach the next milestone.”</p>
                            @endif
                        </div>
                    @endif

                    @if ($campaign->has_end_date && $campaign->end_date)
                        <p class="mt-4 text-center text-xs text-slate-400">
                            Campaign ends {{ myrTime($campaign->end_date, withLabel: false, format: 'M j, Y') }}
                        </p>
                    @endif
                </div>
            @endif

            @if ($contentImage)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contentImage) }}"
                    alt=""
                    class="w-full max-h-[28rem] rounded-xl object-cover"
                />
            @endif

            @if ($contentMessage)
                <div x-data="{ expanded: false }" class="text-base/7 text-slate-600">
                    <div x-show="! expanded">
                        <p class="whitespace-pre-wrap">{{ $messageShort }}</p>
                        @if ($messageIsLong)
                            <button
                                type="button"
                                x-on:click="expanded = true"
                                class="mt-2 text-sm font-semibold text-teal-700 hover:text-teal-800"
                            >
                                Read more &darr;
                            </button>
                        @endif
                    </div>
                    <div x-show="expanded" x-cloak>
                        {!! nl2br(e($contentMessage)) !!}
                        <button
                            type="button"
                            x-on:click="expanded = false"
                            class="mt-2 block text-sm font-semibold text-teal-700 hover:text-teal-800"
                        >
                            Read less &uarr;
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right column: donation form --}}
        <div id="donation-form-panel" class="scroll-mt-4 md:sticky md:top-8 md:self-start lg:pl-4">
        @livewire('donation-form', ['campaign' => $campaign, 'isPublicPage' => true], key('donation-form-'.$campaign->public_id))
    </div>
</div>

{{-- Mobile-only sticky CTA: the form sits below the fold on phones, so keep a
     donate shortcut visible until the donor reaches the form itself. --}}
<div
    wire:ignore
    x-data="{ formVisible: false }"
    x-init="new IntersectionObserver(
        (entries) => { formVisible = entries[0].isIntersecting; },
        { threshold: 0.1 }
    ).observe(document.getElementById('donation-form-panel'))"
    x-show="! formVisible"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="translate-y-full"
    x-transition:enter-end="translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-y-0"
    x-transition:leave-end="translate-y-full"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur lg:hidden"
>
    <button
        type="button"
        x-on:click="document.getElementById('donation-form-panel').scrollIntoView({ behavior: 'smooth', block: 'start' })"
        class="min-h-12 w-full rounded-xl bg-teal-600 text-base font-bold text-white shadow-lg transition hover:bg-teal-700 active:scale-[0.99]"
    >
        Donate now
    </button>
</div>
</main>
</div>
