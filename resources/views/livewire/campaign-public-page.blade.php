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

<main class="max-w-6xl mx-auto px-4 py-12">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Left column: campaign details --}}
        <div class="space-y-6">
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

            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
                    {{ $contentTitle }}
                </h1>
            </div>

            @if ($showTotalRaised)
                @php
                    $raised = (float) $campaign->collected_amount;
                    $target = (float) ($campaign->target_amount ?? 0);
                    $progressPercent = $target > 0 ? min(100, ($raised / $target) * 100) : 0;
                    $checkpoints = [0, $target * 0.1, $target * 0.25, $target * 0.5, $target];
                    $segments = [];
                    for ($i = 1; $i < count($checkpoints); $i++) {
                        $start = $checkpoints[$i - 1];
                        $end = $checkpoints[$i];
                        $range = $end - $start;
                        if ($range <= 0) {
                            $fill = 0;
                        } elseif ($raised >= $end) {
                            $fill = 100;
                        } elseif ($raised <= $start) {
                            $fill = 0;
                        } else {
                            $fill = (($raised - $start) / $range) * 100;
                        }
                        $segments[] = ['start' => $start, 'end' => $end, 'range' => $range, 'fill' => $fill];
                    }

                    $currentCheckpointIndex = null;
                    foreach ($checkpoints as $index => $amount) {
                        if ($amount > $raised) {
                            $currentCheckpointIndex = $index;
                            break;
                        }
                    }
                    $goalReached = $currentCheckpointIndex === null && $raised >= $target && $target > 0;
                    $currentCheckpointAmount = $checkpoints[$currentCheckpointIndex] ?? $target;
                    $leftToNext = max(0, $currentCheckpointAmount - $raised);
                @endphp

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-slate-500">Raised</p>
                            <p class="text-2xl font-bold text-slate-900">RM {{ number_format($raised, 2) }}</p>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Goal RM {{ number_format($target, 2) }}</p>
                    </div>

                    @if ($target > 0)
                        <div class="mt-6">
                            <div class="relative mb-7">
                                {{-- Percentage label --}}
                                <span
                                    class="absolute -top-5 -translate-x-1/2 text-xs font-bold text-[#10b981] transition-all duration-1000 ease-out"
                                    style="left: {{ $progressPercent }}%"
                                >
                                    {{ number_format($progressPercent, 1) }}%
                                </span>

                                {{-- Segmented progress bar --}}
                                <div class="flex h-3 gap-1">
                                    @foreach ($segments as $segment)
                                        <div class="relative h-full flex-1 overflow-hidden rounded-full bg-slate-200">
                                            <div
                                                class="absolute left-0 top-0 h-full rounded-full bg-[#10b981] transition-all duration-1000 ease-out"
                                                style="width: {{ $segment['fill'] }}%"
                                            ></div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Checkpoint dots --}}
                                @foreach ($checkpoints as $index => $amount)
                                    @php
                                        $position = ($amount / $target) * 100;
                                        $isCompleted = $index > 0 && $raised >= $amount;
                                        $isCurrent = $index === $currentCheckpointIndex;
                                    @endphp
                                    <div
                                        class="absolute top-1/2 -translate-y-1/2"
                                        style="left: calc({{ $position }}% - 6px)"
                                    >
                                        @if ($isCurrent)
                                            <span class="block size-4 animate-pulse rounded-full border-2 border-white bg-white shadow-sm ring-2 ring-[#10b981]"></span>
                                        @elseif ($isCompleted || $index === 0)
                                            <span class="block size-3 rounded-full bg-[#10b981]"></span>
                                        @else
                                            <span class="block size-3 rounded-full bg-slate-300"></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Checkpoint labels --}}
                            <div class="grid grid-cols-5 gap-1">
                                @foreach ($checkpoints as $index => $amount)
                                    @php
                                        $isCurrent = $index === $currentCheckpointIndex;
                                        $label = $amount === 0 ? 'RM0' : 'RM'.number_format($amount / 1000, 0).'k';
                                    @endphp
                                    <div class="text-center">
                                        <span class="text-[10px] font-semibold leading-none {{ $isCurrent ? 'text-[#10b981]' : 'text-slate-400' }}">
                                            {{ $label }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Next milestone callout --}}
                        <div class="mt-5 rounded-lg border border-emerald-100 bg-emerald-50 p-3">
                            @if ($goalReached)
                                <p class="text-sm font-semibold text-emerald-800">🎉 Goal reached!</p>
                                <p class="mt-0.5 text-xs text-emerald-600">Thank you for helping us hit the target.</p>
                            @else
                                <p class="text-xs font-medium text-emerald-700">Next milestone: RM {{ number_format($currentCheckpointAmount, 2) }}</p>
                                <p class="mt-1 text-sm font-semibold text-emerald-900">RM {{ number_format($leftToNext, 2) }} left to reach next milestone</p>
                                <p class="mt-1 text-xs text-emerald-600">“Almost there! Help us reach the next milestone.”</p>
                            @endif
                        </div>
                    @endif

                    @if ($campaign->has_end_date && $campaign->end_date)
                        <p class="mt-4 text-center text-xs text-slate-400">
                            Campaign ends {{ $campaign->end_date->format('M j, Y') }}
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
                        <p>{{ $messageShort }}</p>
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
        <div class="md:sticky md:top-8 md:self-start lg:pl-4">
            @livewire('donation-form', ['campaign' => $campaign, 'isPublicPage' => true], key('donation-form-'.$campaign->public_id))
        </div>
    </div>
</main>
